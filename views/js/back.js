/**
* 2007-2024 PrestaShop
*/

document.addEventListener('DOMContentLoaded', function() {
    // Agregar estilos CSS para mejorar la apariencia
    const style = document.createElement('style');
    style.textContent = `
        .panel-heading-action {
            float: right;
            margin-top: -3px;
        }
        .panel-heading-action .btn-group .btn {
            border-radius: 3px;
            font-size: 12px;
            padding: 6px 12px;
            transition: all 0.3s ease;
        }
        .panel-heading-action .btn-group .btn:first-child {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .panel-heading-action .btn-group .btn:last-child {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: none;
        }
        .panel-heading-action .btn.active {
            box-shadow: inset 0 3px 5px rgba(0,0,0,.125);
        }
        .panel-heading-action .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .panel-heading i.icon-user {
            margin-right: 8px;
            color: #555;
        }
    `;
    document.head.appendChild(style);

    // Código para los botones de filtrado
    const showAllBtn = document.getElementById('showAllEmployees');
    const showCommercialsBtn = document.getElementById('showOnlyCommercials');
    const listContainer = document.getElementById('gestioncomerciales_commercial_list');
    
    if (showAllBtn && showCommercialsBtn && listContainer) {
        showAllBtn.addEventListener('click', () => {
            updateEmployeeList(false);
            // Cambiar el estado visual de los botones
            showAllBtn.classList.add('active');
            showAllBtn.classList.remove('btn-default');
            showAllBtn.classList.add('btn-primary');
            
            showCommercialsBtn.classList.remove('active');
            showCommercialsBtn.classList.remove('btn-primary');
            showCommercialsBtn.classList.add('btn-default');
        });
        
        showCommercialsBtn.addEventListener('click', () => {
            updateEmployeeList(true);
            // Cambiar el estado visual de los botones
            showCommercialsBtn.classList.add('active');
            showCommercialsBtn.classList.remove('btn-default');
            showCommercialsBtn.classList.add('btn-primary');
            
            showAllBtn.classList.remove('active');
            showAllBtn.classList.remove('btn-primary');
            showAllBtn.classList.add('btn-default');
        });
    }

    // Código para el checkbox "Seleccionar todos"
    const checkAllBox = document.getElementById('checkAll');
    if (checkAllBox) {
        checkAllBox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="id_clients[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    function updateEmployeeList(onlyCommercials) {
        if (!listContainer) return;
        
        const baseUrl = listContainer.dataset.ajaxUrl;
        
        if (!baseUrl) {
            console.error('URL de AJAX no encontrada');
            return;
        }

        const url = new URL(baseUrl);
        url.searchParams.append('ajax', '1');
        url.searchParams.append('action', 'getEmployees');
        url.searchParams.append('only_commercials', onlyCommercials ? '1' : '0');
        url.searchParams.append('token', getTokenFromUrl(baseUrl));

        fetch(url, {
            method: 'GET', // Cambiar a GET para mejor compatibilidad
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(employees => {
            const tableBody = document.querySelector('#gestioncomerciales_commercial_list tbody');
            if (!tableBody) return;

            tableBody.innerHTML = '';
            
            if (employees.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td colspan="5" class="text-center">
                        <em>${onlyCommercials ? 'No hay comerciales registrados' : 'No hay empleados registrados'}</em>
                    </td>
                `;
                tableBody.appendChild(row);
                return;
            }

            employees.forEach(employee => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="fixed-width-xs center">${employee.id}</td>
                    <td>${employee.firstname}</td>
                    <td>${employee.lastname}</td>
                    <td class="center">${employee.profile || 'Sin perfil'}</td>
                    <td class="text-right">
                        <div class="btn-group-action">
                            <div class="btn-group pull-right">
                                <a href="#" class="edit btn btn-default" title="Editar">
                                    <i class="icon-pencil"></i> Editar
                                </a>
                                <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    <i class="icon-caret-down"></i>&nbsp;
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="#" class="delete" title="Eliminar">
                                            <i class="icon-trash"></i> Eliminar
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            const tableBody = document.querySelector('#gestioncomerciales_commercial_list tbody');
            if (tableBody) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center alert alert-danger">
                            Error al cargar los datos. Por favor, recarga la página.
                        </td>
                    </tr>
                `;
            }
        });
    }

    // Función auxiliar para extraer el token de la URL
    function getTokenFromUrl(url) {
        const urlParams = new URLSearchParams(url.split('?')[1]);
        return urlParams.get('token') || '';
    }

    // NO cargar automáticamente - dejar que PHP cargue los comerciales por defecto
    // El listado ya se renderiza desde PHP con solo comerciales
});