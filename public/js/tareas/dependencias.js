// Funciones para gestionar dependencias entre tareas
// Similar a MS Project

let dependenciasActuales = [];
let tareasDisponibles = []; // Para el selector de dependencias

// Configuración de tipos de relación
const tiposRelacionConfig = {
    'FS': { nombre: 'Fin-Inicio (Estándar)', icono: '➜', descripcion: 'La tarea dependiente comienza cuando termina la anterior' },
    'SS': { nombre: 'Inicio-Inicio', icono: '⇒', descripcion: 'Ambas tareas comienzan al mismo tiempo' },
    'FF': { nombre: 'Fin-Fin', icono: '⟹', descripcion: 'Ambas tareas terminan al mismo tiempo' },
    'SF': { nombre: 'Inicio-Fin', icono: '⇐', descripcion: 'La tarea dependiente termina cuando comienza la anterior' }
};

/**
 * Cargar las tareas disponibles para ser seleccionadas como dependencias
 * @param {number} tareaIdExcluir - ID de la tarea a excluir (para no depender de sí misma)
 * @param {number} milestoneId - Opcional: limitar a tareas del mismo milestone
 */
async function cargarTareasParaDependencias(tareaIdExcluir, milestoneId = null) {
    try {
        let url = '/hoshin_kanri/app/tareas/lista.php?para_dependencia=1&excluir=' + tareaIdExcluir;
        
        if (milestoneId && milestoneId > 0) {
            url += '&milestone_id=' + milestoneId;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            tareasDisponibles = data.tareas || [];
            return tareasDisponibles;
        }
    } catch (error) {
        console.error('Error cargando tareas para dependencias:', error);
    }
    return [];
}

/**
 * Cargar las dependencias de una tarea
 * @param {number} tareaId - ID de la tarea
 */
async function cargarDependencias(tareaId) {
    try {
        const response = await fetch(`/hoshin_kanri/app/tareas/listar_dependencias.php?tarea_id=${tareaId}`);
        const data = await response.json();
        
        if (data.success) {
            dependenciasActuales = data.dependencias || [];
            return {
                dependencias: data.dependencias,
                dependientes: data.dependientes
            };
        }
    } catch (error) {
        console.error('Error cargando dependencias:', error);
    }
    return { dependencias: [], dependientes: [] };
}

/**
 * Agregar una dependencia entre tareas
 * @param {number} tareaId - ID de la tarea principal
 * @param {number} tareaDependienteId - ID de la tarea de la que depende
 * @param {string} tipoRelacion - Tipo: FS, SS, FF, SF
 * @param {number} diasDesfase - Días de desfase
 */
async function agregarDependencia(tareaId, tareaDependienteId, tipoRelacion = 'FS', diasDesfase = 0) {
    try {
        const formData = new FormData();
        formData.append('tarea_id', tareaId);
        formData.append('tarea_dependiente_id', tareaDependienteId);
        formData.append('tipo_relacion', tipoRelacion);
        formData.append('dias_desfase', diasDesfase);
        
        const response = await fetch('/hoshin_kanri/app/tareas/agregar_dependencia.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            // Recargar dependencias
            await cargarDependencias(tareaId);
            return { success: true, message: data.message };
        } else {
            return { success: false, message: data.message };
        }
    } catch (error) {
        console.error('Error agregando dependencia:', error);
        return { success: false, message: 'Error al agregar dependencia' };
    }
}

/**
 * Eliminar una dependencia
 * @param {number} dependenciaId - ID de la dependencia
 * @param {number} tareaId - ID de la tarea (para recargar)
 */
async function eliminarDependencia(dependenciaId, tareaId) {
    try {
        const formData = new FormData();
        formData.append('dependencia_id', dependenciaId);
        formData.append('tarea_id', tareaId);
        
        const response = await fetch('/hoshin_kanri/app/tareas/eliminar_dependencia.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            // Recargar dependencias
            await cargarDependencias(tareaId);
            return { success: true, message: data.message };
        } else {
            return { success: false, message: data.message };
        }
    } catch (error) {
        console.error('Error eliminando dependencia:', error);
        return { success: false, message: 'Error al eliminar dependencia' };
    }
}

/**
 * Mostrar las dependencias en un elemento HTML
 * @param {number} tareaId - ID de la tarea
 * @param {string} selectorElemento - Selector CSS del elemento donde mostrar
 */
async function mostrarDependencias(tareaId, selectorElemento = '#dependenciasContainer') {
    const resultado = await cargarDependencias(tareaId);
    const elemento = document.querySelector(selectorElemento);
    
    if (!elemento) return;
    
    let html = '<div class="card mt-3 border-info">';
    html += '<div class="card-header bg-info bg-opacity-10 border-info">';
    html += '<h6 class="mb-0 text-info"><i class="fas fa-link me-2"></i>Dependencias de Tareas</h6>';
    html += '</div>';
    html += '<div class="card-body">';
    
    // Mostrar tareas de las que depende esta
    if (resultado.dependencias.length > 0) {
        html += '<div class="mb-4">';
        html += '<h6 class="mb-3"><i class="fas fa-arrow-down text-warning me-2"></i>Esta tarea depende de:</h6>';
        html += '<div class="list-group list-group-sm">';
        resultado.dependencias.forEach(dep => {
            const config = tiposRelacionConfig[dep.tipo_relacion] || {};
            const diasText = dep.dias_desfase !== 0 ? ` + ${dep.dias_desfase} día${dep.dias_desfase !== 1 ? 's' : ''}` : '';
            
            html += `
            <div class="list-group-item d-flex justify-content-between align-items-start py-3">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <span class="badge bg-warning me-2">${config.icono} ${dep.tipo_relacion}</span>
                        <strong class="text-dark">${dep.titulo_tarea_dependiente}</strong>
                    </div>
                    <small class="text-muted d-block mb-1">
                        ${config.nombre}${diasText}
                    </small>
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i>${dep.nombre_responsable} | 
                        <i class="fas fa-calendar me-1"></i>${dep.fecha_fin_dependiente}
                    </small>
                </div>
                <button class="btn btn-sm btn-link text-danger p-0" onclick="eliminarDependenciaUI(${dep.tarea_dependencia_id}, ${tareaId})" title="Eliminar">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
        });
        html += '</div>';
        html += '</div>';
    }
    
    // Mostrar tareas que dependen de esta
    if (resultado.dependientes.length > 0) {
        html += '<div>';
        html += '<h6 class="mb-3"><i class="fas fa-arrow-up text-danger me-2"></i>Tareas que dependen de esta:</h6>';
        html += '<div class="list-group list-group-sm">';
        resultado.dependientes.forEach(dep => {
            const config = tiposRelacionConfig[dep.tipo_relacion] || {};
            const diasText = dep.dias_desfase !== 0 ? ` + ${dep.dias_desfase} día${dep.dias_desfase !== 1 ? 's' : ''}` : '';
            
            html += `
            <div class="list-group-item d-flex justify-content-between align-items-start py-3">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <span class="badge bg-danger me-2">${config.icono} ${dep.tipo_relacion}</span>
                        <strong class="text-dark">${dep.titulo_tarea}</strong>
                    </div>
                    <small class="text-muted d-block mb-1">
                        ${config.nombre}${diasText}
                    </small>
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i>${dep.nombre_responsable} | 
                        <i class="fas fa-calendar me-1"></i>${dep.fecha_fin_tarea}
                    </small>
                </div>
            </div>`;
        });
        html += '</div>';
        html += '</div>';
    }
    
    if (resultado.dependencias.length === 0 && resultado.dependientes.length === 0) {
        html += '<div class="alert alert-light mb-0 border-0"><small class="text-muted"><i class="fas fa-info-circle me-2"></i>No hay dependencias configuradas para esta tarea.</small></div>';
    }
    
    html += '</div></div>';
    elemento.innerHTML = html;
}

/**
 * Mostrar formulario para agregar dependencia
 * @param {number} tareaId - ID de la tarea
 * @param {string} selectorElemento - Selector CSS del elemento donde mostrar
 * @param {number} milestoneId - ID del milestone actual (opcional)
 */
async function mostrarFormularioDependencia(tareaId, selectorElemento = '#formularioDependenciaContainer', milestoneId = null) {
    const elemento = document.querySelector(selectorElemento);
    if (!elemento) return;
    
    // Si no se pasa milestone, intentar obtenerlo del formulario
    if (!milestoneId) {
        const milestoneSelect = document.getElementById('tareaMilestoneId');
        if (milestoneSelect) {
            milestoneId = parseInt(milestoneSelect.value);
        }
    }
    
    // Cargar tareas disponibles del mismo milestone
    const tareas = await cargarTareasParaDependencias(tareaId, milestoneId);
    
    let html = '<div class="card border-success mt-3">';
    html += '<div class="card-header bg-success bg-opacity-10 border-success">';
    html += '<h6 class="mb-0 text-success"><i class="fas fa-plus-circle me-2"></i>Agregar Dependencia</h6>';
    html += '</div>';
    html += '<div class="card-body">';
    
    if (tareas.length === 0) {
        html += '<div class="alert alert-warning mb-0"><small><i class="fas fa-exclamation-triangle me-2"></i>No hay tareas disponibles en este milestone para crear dependencias.</small></div>';
        elemento.innerHTML = html + '</div></div>';
        return;
    }
    
    html += '<div class="form-group mb-3">';
    html += '<label for="tareaDependiente" class="form-label">Seleccionar tarea de la que depende:</label>';
    html += '<select class="form-select form-select-sm" id="tareaDependiente">';
    html += '<option value="">-- Seleccionar --</option>';
    tareas.forEach(tarea => {
        const estado = tarea.completada ? '✓' : '○';
        html += `<option value="${tarea.tarea_id}">${estado} ${tarea.titulo}</option>`;
    });
    html += '</select>';
    html += '<small class="form-text text-muted d-block mt-1">Elige la tarea que debe completarse primero</small>';
    html += '</div>';
    
    html += '<div class="form-group mb-3">';
    html += '<label for="tipoRelacion" class="form-label">Tipo de relación:</label>';
    html += '<select class="form-select form-select-sm" id="tipoRelacion">';
    Object.entries(tiposRelacionConfig).forEach(([key, value]) => {
        html += `<option value="${key}" ${key === 'FS' ? 'selected' : ''}>${value.icono} ${value.nombre}</option>`;
    });
    html += '</select>';
    html += '<small class="form-text text-muted d-block mt-1" id="descRelacion">La tarea dependiente comienza cuando termina la anterior</small>';
    html += '</div>';
    
    html += '<div class="form-group mb-3">';
    html += '<label for="diasDesfase" class="form-label">Días de desfase (opcional):</label>';
    html += '<input type="number" class="form-control form-control-sm" id="diasDesfase" value="0" min="0">';
    html += '<small class="form-text text-muted d-block mt-1">Días adicionales entre el fin de una tarea y el inicio de la siguiente</small>';
    html += '</div>';
    
    html += '<div class="d-flex gap-2">';
    html += '<button class="btn btn-sm btn-success" onclick="guardarDependenciaUI(' + tareaId + ')"><i class="fas fa-check me-1"></i>Agregar Dependencia</button>';
    html += '<button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById(\'formularioDependenciaContainer\').innerHTML=\'\'" type="button">Cancelar</button>';
    html += '</div>';
    
    html += '</div></div>';
    
    elemento.innerHTML = html;
    
    // Event listener para actualizar descripción
    const selectRelacion = document.getElementById('tipoRelacion');
    if (selectRelacion) {
        selectRelacion.addEventListener('change', function() {
            const config = tiposRelacionConfig[this.value] || {};
            const descElement = document.getElementById('descRelacion');
            if (descElement) {
                descElement.textContent = config.descripcion || '';
            }
        });
    }
}

/**
 * Wrapper para UI - Guardar dependencia
 */
async function guardarDependenciaUI(tareaId) {
    const tareaDependiente = document.getElementById('tareaDependiente').value;
    const tipoRelacion = document.getElementById('tipoRelacion').value;
    const diasDesfase = parseInt(document.getElementById('diasDesfase').value) || 0;
    
    if (!tareaDependiente) {
        Swal.fire({
            icon: 'warning',
            title: 'Validación',
            text: 'Por favor selecciona una tarea'
        });
        return;
    }
    
    const resultado = await agregarDependencia(tareaId, parseInt(tareaDependiente), tipoRelacion, diasDesfase);
    
    if (resultado.success) {
        Swal.fire({
            icon: 'success',
            title: 'Dependencia Agregada',
            text: resultado.message,
            timer: 2000,
            showConfirmButton: false
        });
        // Recargar dependencias y formulario
        await mostrarDependencias(tareaId);
        await mostrarFormularioDependencia(tareaId);
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: resultado.message
        });
    }
}

/**
 * Wrapper para UI - Eliminar dependencia
 */
async function eliminarDependenciaUI(dependenciaId, tareaId) {
    Swal.fire({
        title: '¿Eliminar dependencia?',
        text: '¿Estás seguro de que deseas eliminar esta dependencia?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const resultado = await eliminarDependencia(dependenciaId, tareaId);
            
            if (resultado.success) {
                // Recargar dependencias
                await mostrarDependencias(tareaId);
                await mostrarFormularioDependencia(tareaId);
                Swal.fire({
                    icon: 'success',
                    title: 'Eliminado',
                    text: resultado.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: resultado.message
                });
            }
        }
    });
}

/**
 * Generar reporte de ruta crítica (tareas interdependientes)
 */
async function generarRutaCritica(tareaId) {
    const resultado = await cargarDependencias(tareaId);
    
    if (resultado.dependencias.length === 0) {
        return 'Esta tarea no tiene dependencias previas.';
    }
    
    let reporte = '📊 Ruta de Dependencias:\n\n';
    resultado.dependencias.forEach((dep, index) => {
        const config = tiposRelacionConfig[dep.tipo_relacion] || {};
        const diasText = dep.dias_desfase !== 0 ? ` + ${dep.dias_desfase} días` : '';
        reporte += `${index + 1}. ${dep.titulo_tarea_dependiente}\n`;
        reporte += `   ${config.icono} ${config.nombre}${diasText}\n`;
        reporte += `   📅 Vence: ${dep.fecha_fin_dependiente}\n`;
        reporte += `   👤 ${dep.nombre_responsable}\n\n`;
    });
    
    return reporte;
}

