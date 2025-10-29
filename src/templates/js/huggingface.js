document.addEventListener('DOMContentLoaded', function () {
    function $id(id) { return document.getElementById(id); }

    const searchBox = $id('hf-search-box');
    const searchBtn = $id('hf-search-btn');
    const resultsDiv = $id('hf-search-results');
    const detailsDiv = $id('hf-model-details');
    const modelTitle = $id('hf-model-title');
    const modelTags = $id('hf-model-tags');
    const modelMeta = $id('hf-model-meta');
    const modelLink = $id('hf-model-link');
    const useBtn = $id('hf-use-model');

    let currentModel = null;

    function clearResults() {
        resultsDiv.innerHTML = '';
        detailsDiv.style.display = 'none';
        useBtn.disabled = true;
        currentModel = null;
    }

    function renderResultItem(item) {
        // item may contain modelId or id
        const id = item.modelId || item.id || item.model || item.model_id;
        const pipeline = item.pipeline_tag || (item.tags && item.tags.indexOf('audio-classification') !== -1 ? 'audio-classification' : '');
        const el = document.createElement('div');
        el.className = 'hf-result-item p-2 border-bottom';
        el.style.cursor = 'pointer';
        el.innerHTML = '<strong>' + id + '</strong>' + (pipeline ? (' <span class="badge badge-light ml-2">' + pipeline + '</span>') : '') + '<div class="small text-muted">' + (item.library_name || (item.tags || []).slice(0,3).join(', ')) + '</div>';
        el.addEventListener('click', function () {
            selectModel(id);
        });
        return el;
    }

    function selectModel(modelId) {
        // fetch model metadata
    const apiBase = (typeof baseUrl !== 'undefined' && baseUrl) ? baseUrl : '';
    // Use query parameter to avoid embedding slashes in the path (encoded slashes may be rejected by some servers)
    fetch(apiBase + '/api/huggingface/model?id=' + encodeURIComponent(modelId), {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(r => { if (!r.ok) return r.text().then(t=>{throw new Error(t||r.status)}); return r.json(); })
            .then(data => {
                currentModel = data;
                modelTitle.textContent = data.id || data.modelId || modelId;
                modelTags.textContent = (data.pipeline_tag ? 'Pipeline: ' + data.pipeline_tag + ' — ' : '') + (Array.isArray(data.tags) ? data.tags.join(', ') : '');
                modelMeta.innerHTML = '';
                if (data.cardData && data.cardData.modelDetails) {
                    modelMeta.textContent = JSON.stringify(data.cardData.modelDetails);
                } else {
                    // show some common fields
                    const fields = ['library_name','pipeline_tag','sha','config','modelId'];
                    fields.forEach(f => { if (data[f]) modelMeta.innerHTML += '<div><strong>' + f + ':</strong> ' + (typeof data[f] === 'string' ? data[f] : JSON.stringify(data[f])) + '</div>'; });
                }
                // Use raw modelId (contains slash) when linking to huggingface website
                modelLink.href = 'https://huggingface.co/' + modelId;
                modelLink.textContent = '⚠️ Check Inference API availability on HuggingFace';
                modelLink.className = 'btn btn-warning btn-sm';
                modelLink.title = 'Open the model page on HuggingFace to verify if "Inference API" is available. Look for the inference widget on the model page or check if it says "Hosted inference API".';
                modelLink.target = '_blank';
                detailsDiv.style.display = 'block';
                useBtn.disabled = false;
            })
            .catch(err => {
                detailsDiv.style.display = 'none';
                useBtn.disabled = true;
                alert('Failed to load model metadata: ' + (err.message || err));
            });
    }

    function doSearch(q) {
        if (!q || q.trim() === '') return;
        resultsDiv.innerHTML = '<div class="p-2 text-muted">Searching...</div>';
    const apiBase = (typeof baseUrl !== 'undefined' && baseUrl) ? baseUrl : '';
    fetch(apiBase + '/api/huggingface/search?q=' + encodeURIComponent(q), {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(r => { if (!r.ok) return r.text().then(t=>{throw new Error(t||r.status)}); return r.json(); })
            .then(data => {
                resultsDiv.innerHTML = '';
                if (!Array.isArray(data) || data.length === 0) {
                    resultsDiv.innerHTML = '<div class="p-2 text-muted">No results</div>';
                    return;
                }
                data.forEach(item => resultsDiv.appendChild(renderResultItem(item)));
            })
            .catch(err => {
                resultsDiv.innerHTML = '<div class="p-2 text-danger">Error: ' + (err.message||err) + '</div>';
            });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function () { doSearch(searchBox.value); });
        searchBox.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); doSearch(searchBox.value); } });
    }

    // public API: open modal
    window.openHuggingFaceModal = function (prefillQuery) {
        clearResults();
        if (prefillQuery) searchBox.value = prefillQuery;
        $('#modal-hf').modal('show');
        if (prefillQuery) doSearch(prefillQuery);
    }

    // when user clicks Use selected model, emit an event with selected metadata
    if (useBtn) {
        useBtn.addEventListener('click', function () {
            if (!currentModel) return;
            const ev = new CustomEvent('huggingface:model:selected', {detail: currentModel});
            window.dispatchEvent(ev);
            $('#modal-hf').modal('hide');
        });
    }
});
