# 📋 Dependencias de Tareas - Documentación

## Descripción

Esta funcionalidad permite gestionar dependencias entre tareas de forma similar a **Microsoft Project**. Ahora los usuarios pueden indicar que una tarea depende de otra, estableciendo relaciones y tipos de dependencia.

## Características

✅ **Crear dependencias** entre tareas con 4 tipos de relaciones:
  - **Fin-Inicio (FS)**: La tarea dependiente comienza cuando termina la anterior
  - **Inicio-Inicio (SS)**: Ambas tareas comienzan al mismo tiempo
  - **Fin-Fin (FF)**: Ambas tareas terminan al mismo tiempo
  - **Inicio-Fin (SF)**: La tarea dependiente termina cuando comienza la anterior

✅ **Desfase de días**: Permite agregar días adicionales entre tareas

✅ **Prevención de dependencias circulares**: El sistema impide crear bucles de dependencias

✅ **Vista completa**: Muestra tanto las tareas de las que depende como las que dependen de la actual

✅ **Auditoría**: Todos los cambios en dependencias son registrados

## Instalación

### Paso 1: Ejecutar la Migración de Base de Datos

1. Abre tu navegador y accede a:
   ```
   http://localhost/hoshin_kanri/database/ejecutar_migracion.php
   ```

2. Verifica que se muestren los mensajes ✅ de éxito

3. Confirma que se crearon las tablas:
   - `tarea_dependencias`
   - `tarea_dependencias_auditoria`

### Paso 2: Verificar Archivos Creados

Los siguientes archivos han sido creados/modificados:

**Backend (PHP)**:
- ✅ `app/tareas/agregar_dependencia.php` - Crear dependencia
- ✅ `app/tareas/eliminar_dependencia.php` - Eliminar dependencia
- ✅ `app/tareas/listar_dependencias.php` - Listar dependencias
- ✅ `app/tareas/lista.php` - Modificado para soportar selector de dependencias

**Frontend (JavaScript)**:
- ✅ `public/js/tareas/dependencias.js` - Gestión de dependencias
- ✅ `public/js/tareas/tareas.js` - Modificado para integrar dependencias
- ✅ `public/tareas.php` - Modificado para mostrar formulario

**Configuración**:
- ✅ `app/layout/footer.php` - Agregado script de dependencias.js
- ✅ `database/migracion_tarea_dependencias.sql` - Script SQL
- ✅ `database/ejecutar_migracion.php` - Script de ejecución

## Uso

### Para Crear una Dependencia

1. Abre la lista de tareas: `http://localhost/hoshin_kanri/public/tareas.php`

2. Haz clic en el botón **✏️ Editar** de una tarea

3. Desplázate hacia abajo en el modal y verás la sección:
   - **"Agregar Dependencia"** - Formulario para crear
   - **"Dependencias de Tareas"** - Muestra dependencias actuales

4. Selecciona:
   - La tarea de la que depende
   - El tipo de relación (FS, SS, FF, SF)
   - Días de desfase (opcional)

5. Haz clic en **"Agregar Dependencia"**

### Para Eliminar una Dependencia

1. En la sección **"Dependencias de Tareas"**

2. Localiza la dependencia a eliminar

3. Haz clic en el botón **✕** rojo

4. Confirma la eliminación

## Tipos de Relación - Ejemplos

| Tipo | Código | Descripción | Ejemplo |
|------|--------|-------------|---------|
| Fin-Inicio | FS | Tarea A debe terminar para que comience B | La revisión debe terminar para comenzar la aprobación |
| Inicio-Inicio | SS | Ambas tareas comienzan al mismo tiempo | Documentación y desarrollo comienzan juntos |
| Fin-Fin | FF | Ambas tareas terminan al mismo tiempo | Ambas deben estar completadas el mismo día |
| Inicio-Fin | SF | Tarea B termina cuando A comienza | No común, pero útil en casos especiales |

## Estructura de Tablas

### tarea_dependencias
```sql
- tarea_dependencia_id (PK)
- tarea_id (FK) - Tarea que depende
- tarea_dependiente_id (FK) - Tarea de la que depende
- tipo_relacion (VARCHAR 50) - FS, SS, FF, SF
- dias_desfase (INT) - Días adicionales
- creado_en (TIMESTAMP)
```

### tarea_dependencias_auditoria
```sql
- auditoria_id (PK)
- tarea_id (FK)
- accion (VARCHAR) - AGREGAR, ELIMINAR
- tarea_dependiente_id (FK)
- usuario_id (FK)
- creado_en (TIMESTAMP)
```

## API REST

### Agregar Dependencia
```
POST /hoshin_kanri/app/tareas/agregar_dependencia.php

Parámetros:
- tarea_id: ID de la tarea principal
- tarea_dependiente_id: ID de la tarea dependiente
- tipo_relacion: FS, SS, FF, SF
- dias_desfase: 0 o más

Respuesta:
{
  "success": true,
  "message": "Dependencia agregada correctamente",
  "dependencia_id": 123
}
```

### Eliminar Dependencia
```
POST /hoshin_kanri/app/tareas/eliminar_dependencia.php

Parámetros:
- dependencia_id: ID de la dependencia a eliminar
- tarea_id: ID de la tarea (para auditoría)

Respuesta:
{
  "success": true,
  "message": "Dependencia eliminada correctamente"
}
```

### Listar Dependencias
```
GET /hoshin_kanri/app/tareas/listar_dependencias.php?tarea_id=123

Respuesta:
{
  "success": true,
  "dependencias": [...], // Tareas de las que depende
  "dependientes": [...]  // Tareas que dependen de esta
}
```

### Tareas Disponibles (para selector)
```
GET /hoshin_kanri/app/tareas/lista.php?para_dependencia=1&excluir=123

Respuesta:
{
  "success": true,
  "tareas": [
    {
      "tarea_id": 1,
      "titulo": "Tarea A",
      "fecha_inicio": "2024-01-01",
      "fecha_fin": "2024-01-10",
      "completada": 0,
      "responsable": "Juan Pérez",
      "milestone_titulo": "Milestone 1"
    }
  ]
}
```

## Funciones JavaScript Disponibles

```javascript
// Cargar tareas disponibles
await cargarTareasParaDependencias(tareaId, milestoneId);

// Cargar dependencias de una tarea
await cargarDependencias(tareaId);

// Agregar dependencia
await agregarDependencia(tareaId, tareaDependienteId, tipoRelacion, diasDesfase);

// Eliminar dependencia
await eliminarDependencia(dependenciaId, tareaId);

// Mostrar dependencias en UI
await mostrarDependencias(tareaId, selectorElemento);

// Mostrar formulario de agregar dependencia
await mostrarFormularioDependencia(tareaId, selectorElemento);

// Generar reporte de ruta crítica
await generarRutaCritica(tareaId);
```

## Validaciones

### El sistema previene:

1. ❌ Que una tarea dependa de sí misma
2. ❌ Dependencias duplicadas (la misma dependencia no puede agregarse dos veces)
3. ❌ Dependencias circulares (A depende de B que depende de A)
4. ❌ Tareas inexistentes como dependencias

## Casos de Uso

### Gestión de Proyectos
- Definir secuencias de tareas
- Identificar el camino crítico
- Prevenir que tareas comiencen antes de tiempo

### Equipos Interdependientes
- Mostrar a qué tareas está esperando un equipo
- Identificar cuellos de botella
- Mejorar la comunicación entre equipos

### Cumplimiento Normativo
- Asegurar que ciertos procesos se completen en orden
- Documentar dependencias reglamentarias
- Auditar el cumplimiento de secuencias

## Limitaciones Conocidas

⚠️ Actualmente no se calcula automáticamente:
- El camino crítico completo
- Desplazamientos de fechas basados en dependencias
- Estimaciones de tiempo total del proyecto

Estas funcionalidades podrían agregarse en versiones futuras.

## Soporte

Para reportar problemas o sugerencias, contacta al equipo de desarrollo.

---

**Versión**: 1.0  
**Fecha**: Mayo 2026  
**Estado**: Producción
