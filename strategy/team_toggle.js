// ================== Team Toggle ==================
    let isBlueTeam = true; // ค่าเริ่มต้นเป็นทีม Ally

    function toggleTeam() {
        const switchLabel = document.querySelector('label[for="teamSwitch"]');
        const agentThumbs = document.querySelectorAll('.agent-thumb');

        // สลับทีม
        isBlueTeam = !isBlueTeam;

        // เปลี่ยนข้อความใน label
        switchLabel.textContent = isBlueTeam ? 'Ally' : 'Enemy';

        // เปลี่ยนสีพื้นหลังของภาพเอเจนต์
        agentThumbs.forEach(agent => {
            agent.style.backgroundColor = isBlueTeam ? 'rgba(0, 123, 255, 0.2)' : 'rgba(255, 0, 0, 0.2)';
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        const agentThumbs = document.querySelectorAll('.agent-thumb');
        agentThumbs.forEach(agent => {
            agent.style.backgroundColor = 'rgba(0, 123, 255, 0.2)';
        });
    });