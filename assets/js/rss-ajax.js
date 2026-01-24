document.addEventListener('DOMContentLoaded', function() {
    const runButtons = document.querySelectorAll('.rss-run-task');
    
    runButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (button.classList.contains('processing')) return;
            
            const taskId = button.dataset.id;
            const originalText = button.innerHTML;
            const notificationContainer = document.getElementById('rss-notifications');
            
            button.classList.add('processing');
            button.innerHTML = '<span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> Ejecutando...';
            button.style.opacity = '0.7';
            button.disabled = true;

            const formData = new FormData();
            formData.append('action', 'rss_ejecutar_tarea_ajax');
            formData.append('tarea_id', taskId);
            formData.append('_ajax_nonce', rss_ajax_obj.nonce);

            fetch(rss_ajax_obj.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                button.classList.remove('processing');
                button.innerHTML = originalText;
                button.style.opacity = '1';
                button.disabled = false;

                const type = data.success ? 'success' : 'error';
                const message = data.data || 'Ocurrió un error desconocido.';
                
                const notification = document.createElement('div');
                notification.className = `flux-notification flux-${type}`;
                notification.innerHTML = `<div class="flux-notification-content"><h4>Resultado</h4><p>${message}</p></div>`;
                
                notificationContainer.prepend(notification);
                
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 500);
                }, 10000);
            })
            .catch(error => {
                console.error('Error:', error);
                button.classList.remove('processing');
                button.innerHTML = originalText;
                button.style.opacity = '1';
                button.disabled = false;
                alert('Error al ejecutar la tarea.');
            });
        });
    });
});
