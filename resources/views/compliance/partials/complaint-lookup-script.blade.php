@push('scripts')
<script>
(function () {
    function debounce(fn, wait) {
        let t;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function setVal(id, value) {
        const el = document.getElementById(id);
        if (el && (value !== undefined && value !== null)) {
            el.value = value;
        }
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function renderItem(type, r) {
        if (type === 'clients') {
            const meta = [r.policy_no, r.product].filter(Boolean).join(' · ');
            const badge = r.source === 'Local'
                ? ' <span class="badge bg-info-subtle text-info-emphasis" style="font-size:0.6rem">Local</span>'
                : (r.source === 'ERP' ? ' <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:0.6rem">ERP</span>' : '');
            return `<div class="fw-semibold">${escapeHtml(r.name || r.policy_no || '—')}${badge}</div>`
                + (meta ? `<div class="small text-muted">${escapeHtml(meta)}</div>` : '');
        }
        const meta = [r.email, r.phone].filter(Boolean).join(' · ');
        return `<div class="fw-semibold">${escapeHtml(r.name || ('Prospect #' + r.id))}</div>`
            + (meta ? `<div class="small text-muted">${escapeHtml(meta)}</div>` : '');
    }

    function applySelection(block, type, r) {
        if (type === 'clients') {
            if (r.name) setVal('complaint-name', r.name);
            if (r.policy_no) setVal('complaint-policy', r.policy_no);
            if (r.email) setVal('complaint-email', r.email);
            if (r.phone) setVal('complaint-phone', r.phone);
            block.querySelector('[data-lookup-input]').value = r.name || r.policy_no || '';
        } else {
            const targetId = block.getAttribute('data-target-id');
            if (targetId) setVal(targetId, r.id);
            block.querySelector('[data-lookup-input]').value = r.name || ('Prospect #' + r.id);
        }
        block.classList.add('has-value');
    }

    if (!document.getElementById('complaint-lookup-styles')) {
        const style = document.createElement('style');
        style.id = 'complaint-lookup-styles';
        style.textContent = `
            .lookup-dropdown [data-lookup-input] { padding-right: 2.2rem; cursor: pointer; }
            .lookup-caret { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; transition: transform 0.15s; font-size: 0.8rem; }
            .lookup-dropdown.open .lookup-caret { transform: translateY(-50%) rotate(180deg); }
            .lookup-dropdown [data-lookup-results] { border: 1px solid var(--agile-border); border-radius: 10px; margin-top: 2px; background: #fff; }
            .lookup-dropdown [data-lookup-results] .list-group-item-action { border: none; border-bottom: 1px solid #f1f5f9; }
            .lookup-dropdown [data-lookup-results] .list-group-item-action:last-child { border-bottom: none; }
            .lookup-dropdown [data-lookup-results] .list-group-item-action:hover { background: var(--agile-primary-muted); }
            .lookup-dropdown [data-lookup-clear] { position: absolute; right: 2rem; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #cbd5e1; font-size: 0.9rem; line-height: 1; padding: 0; display: none; }
            .lookup-dropdown.has-value [data-lookup-clear] { display: inline-block; }
            .lookup-dropdown.has-value [data-lookup-clear]:hover { color: #64748b; }
        `;
        document.head.appendChild(style);
    }

    document.querySelectorAll('[data-lookup]').forEach(function (block) {
        if (block.dataset.lookupBound) return;
        block.dataset.lookupBound = '1';
        block.classList.add('lookup-dropdown');
        const type = block.getAttribute('data-lookup');
        const url = block.getAttribute('data-url');
        const input = block.querySelector('[data-lookup-input]');
        const results = block.querySelector('[data-lookup-results]');
        if (!input || !results || !url) return;

        if (!block.querySelector('.lookup-caret')) {
            const caret = document.createElement('i');
            caret.className = 'bi bi-chevron-down lookup-caret';
            block.appendChild(caret);
        }
        if (!block.querySelector('[data-lookup-clear]')) {
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.setAttribute('data-lookup-clear', '');
            clear.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
            block.appendChild(clear);
            clear.addEventListener('click', function (e) {
                e.stopPropagation();
                input.value = '';
                const targetId = block.getAttribute('data-target-id');
                if (targetId) setVal(targetId, '');
                block.classList.remove('has-value');
                doSearch();
                input.focus();
            });
        }
        if (input.value.trim() !== '') block.classList.add('has-value');

        function open() { results.classList.remove('d-none'); block.classList.add('open'); }
        function hide() { results.classList.add('d-none'); block.classList.remove('open'); }

        function fetchAndRender() {
            const q = input.value.trim();
            fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(data => {
                    const rows = (data && data.results) || [];
                    if (!rows.length) {
                        results.innerHTML = '<div class="list-group-item text-muted small">'
                            + (q.length ? 'No matches found' : 'Type to search…') + '</div>';
                        open();
                        return;
                    }
                    results.innerHTML = rows.map((r, i) =>
                        `<button type="button" class="list-group-item list-group-item-action" data-idx="${i}">${renderItem(type, r)}</button>`
                    ).join('');
                    open();
                    results.querySelectorAll('[data-idx]').forEach(btn => {
                        btn.addEventListener('click', function () {
                            applySelection(block, type, rows[parseInt(this.getAttribute('data-idx'), 10)]);
                            hide();
                        });
                    });
                })
                .catch(() => hide());
        }

        const doSearch = debounce(fetchAndRender, 200);

        input.addEventListener('input', function () {
            block.classList.toggle('has-value', input.value.trim() !== '');
            if (type === 'prospects') {
                const targetId = block.getAttribute('data-target-id');
                if (input.value.trim() === '' && targetId) setVal(targetId, '');
            }
            doSearch();
        });
        input.addEventListener('focus', fetchAndRender);
        input.addEventListener('click', function () { if (results.classList.contains('d-none')) fetchAndRender(); });

        document.addEventListener('click', function (e) {
            if (!block.contains(e.target)) hide();
        });
    });
})();
</script>
@endpush
