@push('scripts')
<script>
(function () {
    var typeEl = document.getElementById('assignedType');
    var userWrap = document.getElementById('assignedUserWrap');
    var agentWrap = document.getElementById('assignedAgentWrap');
    var userSel = document.getElementById('assignedUser');
    var agentSel = document.getElementById('assignedAgent');
    if (!typeEl) return;

    function sync() {
        var t = typeEl.value;
        if (userWrap) userWrap.style.display = t === 'user' ? '' : 'none';
        if (agentWrap) agentWrap.style.display = t === 'agent' ? '' : 'none';
        if (t !== 'user' && userSel) userSel.value = '';
        if (t !== 'agent' && agentSel) agentSel.value = '';
    }

    typeEl.addEventListener('change', sync);
})();
</script>
@endpush
