# Guía Rápida - Dependencias de Tareas

## 🚀 Inicio Rápido (5 minutos)

### 1️⃣ Ejecutar la Migración de BD (1 minuto)

Accede a:
```
http://localhost/hoshin_kanri/database/ejecutar_migracion.php
```

Deberías ver mensajes como:
- ✅ Tabla `tarea_dependencias` creada
- ✅ Tabla `tarea_dependencias_auditoria` creada

### 2️⃣ Usar la Funcionalidad (2 minutos)

**Crear Dependencia:**
1. Ve a Tareas: `http://localhost/hoshin_kanri/public/tareas.php`
2. Edita una tarea (✏️)
3. Desplázate al final del modal
4. En "Agregar Dependencia", selecciona:
   - La tarea que debe terminar primero
   - El tipo de relación (Fin-Inicio es estándar)
   - Haz clic en "Agregar"

**Ver Dependencias:**
- En la sección "Dependencias de Tareas" del modal

**Eliminar:**
- Haz clic en el ✕ rojo junto a la dependencia

### 3️⃣ Entender los Tipos de Relación (2 minutos)

```
📌 Fin-Inicio (FS)      → Tarea A termina → Tarea B comienza
📌 Inicio-Inicio (SS)   → Tarea A comienza → Tarea B comienza
📌 Fin-Fin (FF)         → Tarea A termina → Tarea B termina
📌 Inicio-Fin (SF)      → Tarea A comienza → Tarea B termina
```

**Ejemplo Real:**
- Revisión (Tarea A) → Aprobación (Tarea B) = **Fin-Inicio**

## 📊 Casos de Uso Comunes

### Proyecto de Software
```
┌─ Diseño ─────────┬─ Desarrollo ──────────┐
│                  └─ Testing ────────────┬─ Despliegue
└──────────────────────────────────────────┘
```

### Gestión de Cambios
```
1. Solicitud → 2. Análisis → 3. Aprobación → 4. Implementación → 5. Validación
```

### Procesos Paralelos
```
Documentación  ─┐
                ├─ Lanzamiento
Publicidad      ─┘
```

## ✅ Checklist de Instalación

- [ ] Ejecuté el script de migración (`ejecutar_migracion.php`)
- [ ] Verifiqué que se crearon las tablas en la BD
- [ ] Abro Tareas y veo la sección "Dependencias"
- [ ] Puedo agregar una dependencia sin errores
- [ ] Puedo eliminar una dependencia

## ❌ Si Algo No Funciona

1. **Las tablas no se crean:**
   - Verifica que la BD tenga permisos para crear tablas
   - Revisa la consola de errores en el navegador (F12)

2. **El formulario de dependencias no aparece:**
   - Limpia caché del navegador (Ctrl+F5)
   - Verifica que `dependencias.js` se carga (mira en Redes de F12)

3. **Error al agregar dependencia:**
   - Verifica que ambas tareas existan
   - Comprueba que no sea la misma tarea
   - Revisa la consola de navegador para ver el error

## 🔗 Enlaces Útiles

- 📖 [Documentación Completa](./DEPENDENCIAS_TAREAS.md)
- 🗄️ [Migraciones SQL](../database/migracion_tarea_dependencias.sql)
- 📱 [API REST](./DEPENDENCIAS_TAREAS.md#api-rest)

## 💡 Tips

1. **Prevención de conflictos**: El sistema automáticamente impide crear ciclos
2. **Auditoría**: Cada cambio en dependencias queda registrado
3. **Sin papeleta**: Solo usuarios que pueden editar tareas pueden agregar dependencias
4. **Datos seguros**: Las dependencias se eliminan automáticamente si se elimina una tarea

---

¿Necesitas ayuda? Revisa la [Documentación Completa](./DEPENDENCIAS_TAREAS.md)
