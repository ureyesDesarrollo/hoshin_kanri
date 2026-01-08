</main>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
<script src="../public/js/dashboard/dashboard.js"></script>
<script src="../public/js/objetivos/objetivos.js"></script>
<script src="../public/js/estrategias/estrategias.js"></script>
<script src="../public/js/responsables/responsables.js"></script>
<script src="../public/js/milestones/milestones.js"></script>
<script src="../public/js/tareas/tareas.js"></script>
<script src="../public/js/utils/utils.js"></script>
<script>
    $(document).ready(function() {

        $('#desktopToggle').on('click', function() {
            $('#sidebar').toggleClass('collapsed');
            $('#mainContent').toggleClass('expanded');

            const icon = $(this).find('i');
            icon.toggleClass('fa-chevron-left fa-chevron-right');
        });

        // Mobile sidebar toggle
        $('#mobileToggle').on('click', function() {
            $('#sidebar').toggleClass('mobile-open');
        });
        $('#btnLogout').on('click', function(e) {
            e.preventDefault();

            Swal.fire({
                title: '¿Cerrar sesión?',
                text: "Se perderá la sesión actual",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../public/logout.php';
                }
            });
        });

    });
</script>

</body>

</html>