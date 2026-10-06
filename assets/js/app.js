/**
 * UniControl - Scripts Frontend
 */

document.addEventListener('DOMContentLoaded', () => {
    // Alerta de confirmação para ações de exclusão ou baixa
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const message = btn.getAttribute('data-confirm') || 'Tem certeza que deseja prosseguir?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Atualização de estoque disponível ao selecionar variação no formulário de entrega
    const variacaoSelect = document.getElementById('variacao_id');
    const saldoDisplay = document.getElementById('saldo_disponivel_display');
    const qtdInput = document.getElementById('quantidade_input');

    if (variacaoSelect && saldoDisplay) {
        const updateSaldo = () => {
            const selectedOption = variacaoSelect.options[variacaoSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.saldo !== undefined) {
                const saldo = parseInt(selectedOption.dataset.saldo, 10);
                saldoDisplay.textContent = `Disponível em estoque: ${saldo} un.`;
                if (saldo <= 0) {
                    saldoDisplay.style.color = '#ef4444';
                } else {
                    saldoDisplay.style.color = '#059669';
                }
                if (qtdInput) {
                    qtdInput.max = saldo;
                }
            } else {
                saldoDisplay.textContent = '';
            }
        };

        variacaoSelect.addEventListener('change', updateSaldo);
        updateSaldo();
    }
});
