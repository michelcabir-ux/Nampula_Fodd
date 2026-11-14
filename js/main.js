/**
 * Nampula Fodd - JavaScript Principal
 * Animações e interações
 */

document.addEventListener('DOMContentLoaded', function () {

    // Menu mobile
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');

    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function () {
            navLinks.classList.toggle('activo');
        });
    }

    // Animação suave de entrada nos cards
    const cards = document.querySelectorAll('.card-restaurante, .card-produto');
    cards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.08) + 's';
    });

    // Botão "Restaurantes perto de mim" (GPS)
    const btnGps = document.getElementById('btnGps');
    if (btnGps) {
        btnGps.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('O seu navegador não suporta geolocalização.');
                return;
            }

            btnGps.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A localizar...';

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    // Redireciona com coordenadas
                    window.location.href = '/nampula-fodd/restaurantes.php?lat=' + lat + '&lng=' + lng;
                },
                function () {
                    alert('Não foi possível obter a sua localização. Verifique as permissões.');
                    btnGps.innerHTML = '<i class="fas fa-location-arrow"></i> Perto de mim';
                }
            );
        });
    }

    // Adicionar ao carrinho (AJAX simples)
    document.querySelectorAll('.btn-add-carrinho').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const produtoId = this.dataset.id;
            const quantidade = 1;

            fetch('/nampula-fodd/cliente/adicionar_carrinho.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'produto_id=' + produtoId + '&quantidade=' + quantidade
            })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    // Feedback visual
                    this.innerHTML = '<i class="fas fa-check"></i> Adicionado!';
                    this.style.background = '#28a745';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-plus"></i> Adicionar';
                        this.style.background = '';
                    }, 1500);
                } else {
                    alert(data.mensagem || 'Erro ao adicionar.');
                }
            })
            .catch(() => alert('Erro de conexão.'));
        });
    });

    // Confirmação de ações
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});
