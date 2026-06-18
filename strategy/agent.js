function selectAgent(el) {
    document.querySelectorAll('.agent-thumb').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    if (selectedMap) {
        enableAgentDragDrop(el.src); // เรียกฟังก์ชันเพื่อเพิ่ม agent ลงใน map
    }
}

function startDrag(event, agentImg) {
    event.dataTransfer.setData('agentImg', agentImg); // เก็บข้อมูลของ Agent ที่ถูกลาก
}