document.addEventListener("DOMContentLoaded", () => {
    const mainMapArea = document.getElementById('mainMapArea');

    // อนุญาตให้วาง (drop) บนพื้นที่แผนที่
    mainMapArea.ondragover = function(event) {
        event.preventDefault();
    };

    // จัดการการวาง Agent ลงบนแผนที่
    mainMapArea.ondrop = function(event) {
        event.preventDefault();
        const agentImg = event.dataTransfer.getData('agentImg'); // ดึงข้อมูล Agent ที่ถูกลาก
        const mainMapArea = document.getElementById('mainMapArea');

        if (agentImg) {
            let agentIcon = document.createElement('img');
            agentIcon.src = agentImg;
            agentIcon.className = 'position-absolute';
            agentIcon.style.left = event.offsetX + 'px';
            agentIcon.style.top = event.offsetY + 'px';
            agentIcon.style.width = '48px';
            agentIcon.style.height = '48px';
            agentIcon.style.transform = 'translate(-50%, -50%)';
            agentIcon.style.zIndex = 10;
            agentIcon.draggable = true;

            // เพิ่มการลากใหม่สำหรับ Agent ที่ถูกวาง
            agentIcon.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('agentImg', agentImg);
            });

            mainMapArea.appendChild(agentIcon);
        }
    };
});

function enableAgentDragDrop(agentImg) {
    const mainMapArea = document.getElementById('mainMapArea');
    let agentIcon = document.createElement('img');
    agentIcon.src = agentImg;
    agentIcon.className = 'position-absolute';
    agentIcon.style.left = '50%';
    agentIcon.style.top = '50%';
    agentIcon.style.width = '48px';
    agentIcon.style.height = '48px';
    agentIcon.style.transform = 'translate(-50%, -50%)';
    agentIcon.style.zIndex = 10;
    agentIcon.draggable = true;

    agentIcon.addEventListener('dragstart', function(e) {
        e.dataTransfer.setData('text/plain', null);
        agentIcon.style.opacity = '0.5';
    });

    agentIcon.addEventListener('dragend', function(e) {
        agentIcon.style.opacity = '1';
        let rect = mainMapArea.getBoundingClientRect();
        let x = e.pageX - rect.left;
        let y = e.pageY - rect.top;
        agentIcon.style.left = x + 'px';
        agentIcon.style.top = y + 'px';
        agentIcon.style.transform = 'translate(-50%, -50%)';
    });

    mainMapArea.appendChild(agentIcon);
    mainMapArea.ondragover = function(e) { e.preventDefault(); };
}