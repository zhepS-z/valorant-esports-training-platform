const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const mysql = require('mysql2/promise');

const app = express();
const server = http.createServer(app);
const io = socketIO(server);

// Create MySQL connection pool
const pool = mysql.createPool({
    host: 'localhost',
    user: 'root',           // Replace with your MySQL username
    password: '',           // Replace with your MySQL password
    database: 'valorant_esports',
    waitForConnections: true,
    connectionLimit: 10
});

app.use(express.static(__dirname + '/public'));

app.get("/", (req, res) => {
    res.sendFile(__dirname + "/public/index.html");
});

// แก้ไข chat history API endpoint
app.get("/api/chat-history", async (req, res) => {
    try {
        const { fromId, toId } = req.query;
        const query = `
            SELECT m.*, u.first_name as sender_name, 
                   u.profile_img as sender_profile
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE (sender_id = ? AND receiver_id = ?) 
            OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC  /* เปลี่ยนจาก DESC เป็น ASC */
        `;
        const [messages] = await pool.execute(query, [fromId, toId, toId, fromId]);
        res.json(messages);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

// Update the recent chats endpoint
app.get("/api/recent-chats/:userId", async (req, res) => {
    try {
        const userId = req.params.userId;
        
        // First get user's team info
        const [userTeam] = await pool.execute(`
            SELECT t.team_id, t.team_name, t.team_logo 
            FROM teams t
            JOIN team_members tm ON t.team_id = tm.team_id
            WHERE tm.user_id = ?
        `, [userId]);

        // Query for both private and team messages
        const query = `
            (
                SELECT 
                    DISTINCT IF(m.sender_id = ?, m.receiver_id, m.sender_id) as other_id,
                    'private' as chat_type,
                    u.first_name as name,
                    u.profile_img as avatar,
                    NULL as team_id,
                    (
                        SELECT message 
                        FROM messages 
                        WHERE ((sender_id = ? AND receiver_id = other_id) 
                        OR (sender_id = other_id AND receiver_id = ?))
                        AND message_type = 'private'
                        ORDER BY created_at DESC LIMIT 1
                    ) as last_message,
                    (
                        SELECT created_at 
                        FROM messages 
                        WHERE ((sender_id = ? AND receiver_id = other_id) 
                        OR (sender_id = other_id AND receiver_id = ?))
                        AND message_type = 'private'
                        ORDER BY created_at DESC LIMIT 1
                    ) as last_message_time
                FROM messages m
                JOIN users u ON IF(m.sender_id = ?, m.receiver_id, m.sender_id) = u.user_id
                WHERE (m.sender_id = ? OR m.receiver_id = ?) 
                AND m.message_type = 'private'
            )
            UNION
            (
                SELECT 
                    NULL as other_id,
                    'team' as chat_type,
                    t.team_name as name,
                    t.team_logo as avatar,
                    t.team_id,
                    (
                        SELECT message 
                        FROM messages 
                        WHERE team_id = t.team_id
                        AND message_type = 'team'
                        ORDER BY created_at DESC LIMIT 1
                    ) as last_message,
                    (
                        SELECT created_at 
                        FROM messages 
                        WHERE team_id = t.team_id
                        AND message_type = 'team'
                        ORDER BY created_at DESC LIMIT 1
                    ) as last_message_time
                FROM teams t
                JOIN team_members tm ON t.team_id = tm.team_id
                WHERE tm.user_id = ?
            )
            ORDER BY last_message_time DESC
        `;

        const [chats] = await pool.execute(query, [
            userId, userId, userId, userId, userId, 
            userId, userId, userId, userId
        ]);

        res.json({
            chats,
            userTeam: userTeam[0] || null
        });
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: err.message });
    }
});

// Add this new endpoint to verify users
app.get("/api/verify-users", async (req, res) => {
    try {
        const { myId, friendId } = req.query;
        const [rows] = await pool.execute(
            `SELECT user_id, first_name 
             FROM users 
             WHERE user_id IN (?, ?)`,
            [myId, friendId]
        );
        
        if (rows.length !== 2) {
            res.status(404).json({ 
                error: 'One or both users not found',
                validUsers: rows.map(r => r.user_id)
            });
            return;
        }
        
        res.json({ 
            success: true,
            users: rows
        });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

// เพิ่ม endpoint สำหรับค้นหา users
app.get("/api/search-users", async (req, res) => {
    try {
        const searchTerm = req.query.q;
        const currentUser = req.query.userId;
        
        const query = `
            SELECT user_id, first_name, profile_img
            FROM users 
            WHERE (first_name LIKE ? OR riot_id LIKE ?)
            AND user_id != ?
            LIMIT 10
        `;
        
        const [users] = await pool.execute(query, 
            [`%${searchTerm}%`, `%${searchTerm}%`, currentUser]
        );
        
        res.json(users);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

// แก้ไข team chat history endpoint เช่นกัน
app.get("/api/team-chat-history/:teamId", async (req, res) => {
    try {
        const teamId = req.params.teamId;
        const query = `
            SELECT m.*, u.first_name as sender_name 
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE m.team_id = ? AND m.message_type = 'team'
            ORDER BY m.created_at ASC  /* ยืนยันว่าใช้ ASC */
        `;
        const [messages] = await pool.execute(query, [teamId]);
        res.json(messages);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

// helper: fetch recent chats for a user (reuse same SQL as /api/recent-chats)
async function fetchRecentChatsForUser(userId) {
    const query = `
        (
            SELECT 
                DISTINCT IF(m.sender_id = ?, m.receiver_id, m.sender_id) as other_id,
                'private' as chat_type,
                u.first_name as name,
                u.profile_img as avatar,
                NULL as team_id,
                (
                    SELECT message 
                    FROM messages 
                    WHERE ((sender_id = ? AND receiver_id = other_id) 
                    OR (sender_id = other_id AND receiver_id = ?))
                    AND message_type = 'private'
                    ORDER BY created_at DESC LIMIT 1
                ) as last_message,
                (
                    SELECT created_at 
                    FROM messages 
                    WHERE ((sender_id = ? AND receiver_id = other_id) 
                    OR (sender_id = other_id AND receiver_id = ?))
                    AND message_type = 'private'
                    ORDER BY created_at DESC LIMIT 1
                ) as last_message_time
            FROM messages m
            JOIN users u ON IF(m.sender_id = ?, m.receiver_id, m.sender_id) = u.user_id
            WHERE (m.sender_id = ? OR m.receiver_id = ?) 
            AND m.message_type = 'private'
        )
        UNION
        (
            SELECT 
                NULL as other_id,
                'team' as chat_type,
                t.team_name as name,
                t.team_logo as avatar,
                t.team_id,
                (
                    SELECT message 
                    FROM messages 
                    WHERE team_id = t.team_id
                    AND message_type = 'team'
                    ORDER BY created_at DESC LIMIT 1
                ) as last_message,
                (
                    SELECT created_at 
                    FROM messages 
                    WHERE team_id = t.team_id
                    AND message_type = 'team'
                    ORDER BY created_at DESC LIMIT 1
                ) as last_message_time
            FROM teams t
            JOIN team_members tm ON t.team_id = tm.team_id
            WHERE tm.user_id = ?
        )
        ORDER BY last_message_time DESC
    `;
    const params = [
        userId, userId, userId, userId, userId,
        userId, userId, userId, userId
    ];
    const [rows] = await pool.execute(query, params);
    return rows;
}

io.on("connect", (socket) => {
    // ให้ client ระบุตัวเองเพื่อเข้าร่วมห้อง user-{id}
    socket.on('identify', (data) => {
        if (!data || !data.userId) return;
        socket.join(`user-${data.userId}`);
        console.log(`Socket ${socket.id} identified as user-${data.userId}`);
    });

    socket.on('join', async (data) => {
        const roomName = [data.myId, data.friendId].sort().join('-');
        socket.join(roomName);
        // ให้ socket เข้าร่วมห้อง user-specific เพื่อรองรับการแจ้งเตือน (redundant safe-join)
        if (data.myId) socket.join(`user-${data.myId}`);
        console.log(`${data.name} (ID: ${data.myId}) joined room ${roomName}`);
    });

    socket.on('join team', async (data) => {
        const teamRoom = `team-${data.teamId}`;
        socket.join(teamRoom);
        // join user room too
        if (data.userId) socket.join(`user-${data.userId}`);
        console.log(`User ${data.userId} joined team room ${teamRoom}`);
    });

    // Allow client to explicitly leave a team room when switching to private chat
    socket.on('leave team', (data) => {
        if (!data?.teamId) return;
        const teamRoom = `team-${data.teamId}`;
        socket.leave(teamRoom);
        if (data.userId) socket.leave(`user-${data.userId}`);
        console.log(`User ${data.userId} left team room ${teamRoom}`);
    });
    
    // Update the chat message socket event
// Update the chat message socket event with detailed logging
socket.on('chat message', async (msg) => {
    try {
        console.log('🔵 Server received message:', {
            fromId: msg.fromId,
            toId: msg.toId,
            teamId: msg.teamId,
            type: msg.type,
            msg: msg.msg
        });

        // Get user info before emitting message
        const [userInfo] = await pool.execute(
            'SELECT first_name FROM users WHERE user_id = ?',
            [msg.fromId]
        );
        
        if (msg.type === 'team') {
            roomName = `team-${msg.teamId}`;
            
            // Verify user is part of the team
            const [teamMember] = await pool.execute(
                'SELECT * FROM team_members WHERE user_id = ? AND team_id = ?',
                [msg.fromId, msg.teamId]
            );

            if (teamMember.length === 0) {
                socket.emit('error', { message: 'You are not a member of this team' });
                return;
            }

            // Set sender name from database
            msg.name = userInfo[0]?.first_name;
        } else {
            roomName = [msg.fromId, msg.toId].sort().join('-');
        }

        // Save message to database
        const query = `
            INSERT INTO messages 
            (sender_id, receiver_id, team_id, message, message_type, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        `;
        
        await pool.execute(query, [
            msg.fromId, 
            msg.toId || null, 
            msg.teamId || null, 
            msg.msg,
            msg.type || 'private'
        ]);

        const payload = {
            fromId: parseInt(msg.fromId),
            toId: msg.toId ? parseInt(msg.toId) : null,
            teamId: msg.teamId ? parseInt(msg.teamId) : null,
            msg: msg.msg,
            name: msg.name || userInfo[0]?.first_name,
            type: msg.type || 'private',
            timestamp: new Date()
        };

        console.log('🔵 Prepared payload:', JSON.stringify(payload));

        if (msg.type === 'team') {
            const teamRoom = `team-${msg.teamId}`;
            console.log('🔵 Emitting to team room:', teamRoom);
            io.to(teamRoom).emit('chat message', payload);
        } else {
            // สำหรับ private: ส่งไปยัง user-specific rooms ของผู้ส่งและผู้รับ
            console.log('🔵 Emitting to private rooms:', {
                fromRoom: `user-${msg.fromId}`,
                toRoom: `user-${msg.toId}`
            });
            
            if (msg.fromId) {
                io.to(`user-${msg.fromId}`).emit('chat message', payload);
                console.log('✅ Sent to sender room: user-' + msg.fromId);
            }
            if (msg.toId) {
                io.to(`user-${msg.toId}`).emit('chat message', payload);
                console.log('✅ Sent to receiver room: user-' + msg.toId);
            }
        }

        // --- NEW: emit updated recent-chats payloads to relevant users ---
        if (msg.type === 'team') {
            const [teamMembers] = await pool.execute(
                'SELECT user_id FROM team_members WHERE team_id = ?',
                [msg.teamId]
            );

            for (const member of teamMembers) {
                try {
                    const recent = await fetchRecentChatsForUser(member.user_id);
                    // fetch user's team info to match /api/recent-chats response
                    const [userTeamRows] = await pool.execute(
                        `SELECT t.team_id, t.team_name, t.team_logo 
                         FROM teams t
                         JOIN team_members tm ON t.team_id = tm.team_id
                         WHERE tm.user_id = ?`,
                        [member.user_id]
                    );
                    const userTeam = userTeamRows[0] || null;

                    io.to(`user-${member.user_id}`).emit('update recent chats', {
                        chats: recent,
                        userTeam
                    });
                } catch (e) {
                    console.error('Failed to fetch/emit recent chats for', member.user_id, e);
                }
            }
        } else {
            // private: notify both sender and receiver
            const recipients = new Set([msg.fromId, msg.toId]);
            for (const uid of recipients) {
                if (!uid) continue;
                try {
                    const recent = await fetchRecentChatsForUser(uid);
                    const [userTeamRows] = await pool.execute(
                        `SELECT t.team_id, t.team_name, t.team_logo 
                         FROM teams t
                         JOIN team_members tm ON t.team_id = tm.team_id
                         WHERE tm.user_id = ?`,
                        [uid]
                    );
                    const userTeam = userTeamRows[0] || null;

                    io.to(`user-${uid}`).emit('update recent chats', {
                        chats: recent,
                        userTeam
                    });
                } catch (e) {
                    console.error('Failed to fetch/emit recent chats for', uid, e);
                }
            }
        }
        // --- end NEW ---
    } catch (err) {
        console.error('❌ Error:', err);
        socket.emit('error', { message: 'Failed to save message' });
    }
});

    // รองรับ typing สำหรับ team และ private
    socket.on('typing', (data) => {
        if (data.type === 'team' && data.teamId) {
            io.to(`team-${data.teamId}`).emit('typing', data);
        } else {
            // broadcast typing to user-specific rooms so recipient receives even if not joined pair-room
            if (data.friendId) io.to(`user-${data.friendId}`).emit('typing', data);
            if (data.myId) io.to(`user-${data.myId}`).emit('typing', data); // optional: echo to self
        }
    });

    socket.on('stop typing', (data) => {
        if (data.type === 'team' && data.teamId) {
            io.to(`team-${data.teamId}`).emit('stop typing', data);
        } else {
            if (data.friendId) io.to(`user-${data.friendId}`).emit('stop typing', data);
            if (data.myId) io.to(`user-${data.myId}`).emit('stop typing', data);
        }
    });
});

server.listen(5000, () => {
    console.log('Server running on port 5000');
});