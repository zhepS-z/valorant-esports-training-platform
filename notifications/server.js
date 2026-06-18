// Socket server สำหรับ notifications เท่านั้น
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const mysql = require('mysql2/promise');
const net = require('net');

const app = express();
const server = http.createServer(app);
const io = socketIO(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

// MySQL pool
const pool = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'valorant_esports',
    waitForConnections: true,
    connectionLimit: 10
});

// Map to store user socket connections
const userSockets = new Map();

io.on('connect', (socket) => {
    console.log('✅ Socket connected:', socket.id);

    // User identifies themselves
    socket.on('identify_user', (data) => {
        if (!data?.userId) return;
        
        userSockets.set(data.userId, socket.id);
        socket.userId = data.userId;
        
        console.log(`👤 User ${data.userId} identified`);
    });

    // Listen for notifications from PHP
    socket.on('trigger_user_notification', async (data) => {
        const { target_user_id, type, title, body, meta } = data;
        
        // Save to DB
        const query = `
            INSERT INTO user_notifications 
            (user_id, type, title, body, meta, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        `;
        
        try {
            await pool.execute(query, [
                target_user_id,
                type,
                title,
                body,
                JSON.stringify(meta || {})
            ]);
            
            // ✅ Send real-time notification
            const targetSocketId = userSockets.get(target_user_id);
            if (targetSocketId) {
                io.to(targetSocketId).emit('notification_received', {
                    type: type,
                    id: Math.floor(Math.random() * 1000000),
                    title: title,
                    body: body,
                    meta: meta,
                    created_at: new Date()
                });
                console.log(`📩 Real-time sent to user ${target_user_id}`);
            } else {
                console.log(`⚠️ User ${target_user_id} not connected`);
            }
        } catch (err) {
            console.error('Error:', err);
        }
    });

    socket.on('disconnect', () => {
        if (socket.userId) {
            userSockets.delete(socket.userId);
            console.log(`❌ User ${socket.userId} disconnected`);
        }
    });
});

// ✅ TCP server รับจาก PHP ด้วย fsockopen
const tcpServer = net.createServer((socket) => {
    console.log('📨 TCP client connected');
    
    socket.on('data', (data) => {
        try {
            const message = data.toString().trim();
            const parsed = JSON.parse(message);
            
            if (parsed.type === 'trigger_user_notification') {
                const { target_user_id, type, title, body, meta } = parsed.data;
                
                // Find user's socket and emit
                const targetSocketId = userSockets.get(target_user_id);
                if (targetSocketId) {
                    io.to(targetSocketId).emit('notification_received', {
                        type: type,
                        id: Math.floor(Math.random() * 1000000),
                        title: title,
                        body: body,
                        meta: meta,
                        created_at: new Date()
                    });
                    console.log(`✅ Notification sent to user ${target_user_id}`);
                } else {
                    console.log(`⚠️ User ${target_user_id} not connected (will be fetched on next poll)`);
                }
            }
        } catch (err) {
            console.error('TCP parse error:', err.message);
        }
    });
    
    socket.on('end', () => console.log('📨 TCP client disconnected'));
    socket.on('error', (err) => console.error('TCP error:', err.message));
});

server.listen(3000, () => {
    console.log('🔔 WebSocket server running on port 3000');
});

tcpServer.listen(3001, '127.0.0.1', () => {
    console.log('📨 TCP server running on port 3001');
});