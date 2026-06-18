let selectedMap = "<?= $mapDir . '/' . $defaultMap ?>";

document.addEventListener("DOMContentLoaded", () => {
    const mainMapArea = document.getElementById('mainMapArea');
    mainMapArea.innerHTML = `<img src="${selectedMap}" id="mainMapImg" style="width:100%;max-width:900px;border-radius:1rem;box-shadow:0 0 24px #0008;">`;
});

function selectMap(el, mapSrc) {
    document.querySelectorAll('.map-thumb').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    selectedMap = mapSrc;
    const mainMapArea = document.getElementById('mainMapArea');
    mainMapArea.innerHTML = `<img src="${mapSrc}" id="mainMapImg" style="width:100%;max-width:900px;border-radius:1rem;box-shadow:0 0 24px #0008;">`;
}

function selectMapDropdown(mapSrc, mapName) {
    selectedMap = mapSrc;
    const mainMapArea = document.getElementById('mainMapArea');
    mainMapArea.innerHTML = `<img src="${mapSrc}" id="mainMapImg" style="width:100%;max-width:900px;border-radius:1rem;box-shadow:0 0 24px #0008;">`;
    document.getElementById('selectedMapImg').src = "<?= $mapButtonDir ?>/" + mapName + ".png";
}