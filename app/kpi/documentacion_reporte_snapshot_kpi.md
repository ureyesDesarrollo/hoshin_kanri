# Documentación técnica y de reglas

## 1. Propósito del archivo
`reporte_snapshot_kpi.php` genera un **reporte KPI semanal en formato HTML**, basado **exclusivamente en el último snapshot** disponible en la tabla `kpi_responsable_semanal` para una empresa determinada. El reporte se **envía por correo electrónico** a una lista predefinida de destinatarios.

El archivo **no confía en los porcentajes almacenados** en la base de datos como fuente de verdad; **recalcula todos los indicadores críticos**, especialmente el *Nivel de Compromiso*.

---

## 2. Alcance funcional
El script realiza las siguientes funciones:

1. Identifica el **último snapshot semanal válido**.
2. Recupera el detalle KPI por responsable (solo rol GERENTE).
3. Recalcula métricas clave (OK, Fallas, Compromiso).
4. Ordena a los responsables de mejor a peor desempeño.
5. Genera indicadores globales y promedios.
6. Construye visualizaciones HTML (tablas y KPIs).
7. Envía el reporte por correo electrónico.

---

## 3. Fuentes de datos

### 3.1 Tabla principal
**`kpi_responsable_semanal`**

Campos relevantes:
- `empresa_id`
- `usuario_id`
- `kpi_id`
- `semana_inicio`
- `semana_fin`
- `total_tareas`
- `cumplidas_a_tiempo`
- `vencidas_no_cumplidas`
- `completadas_tarde`
- `porcentaje` (NO usado como fuente de verdad)
- `generado_en`

### 3.2 Tablas relacionadas
- `usuarios`
- `usuarios_empresas`
- `roles`
- `areas`

---

## 4. Reglas de filtrado de datos

### 4.1 Empresa
- Se procesa **una sola empresa**, definida por:
  ```php
  $empresaId = 1;
  ```

### 4.2 Snapshot válido
Solo se consideran registros que cumplan:
- `empresa_id = $empresaId`
- `semana_inicio <> '0000-00-00'`
- `generado_en = (MAX generado_en)`

### 4.3 Responsables incluidos
- Únicamente usuarios con:
  ```sql
  r.nombre = 'GERENTE'
  ```
- Usuario activo en la empresa (`usuarios_empresas.activo = 1`).

---

## 5. Definiciones y reglas de negocio

### 5.1 Métricas base

| Métrica | Definición |
|------|-----------|
| OK | Tareas cumplidas a tiempo (`cumplidas_a_tiempo`) |
| Vencidas | Tareas vencidas no cumplidas |
| Tarde | Tareas completadas fuera de tiempo |
| Fallas | `Vencidas + Tarde` |

Todos los valores:
- Se convierten a entero
- Valores `NULL` o vacíos se consideran **0**

---

## 6. Regla central: Nivel de Compromiso

### 6.1 Fórmula oficial

```
Compromiso (%) = OK / (OK + Vencidas + Tarde) * 100
```

Equivalente a:
```
100 - (Fallas / (OK + Fallas)) * 100
```

### 6.2 Reglas de cálculo
- Si el denominador es `0` → compromiso = `0%`
- El resultado:
  - Se redondea al entero más cercano
  - Se limita entre `0` y `100`

### 6.3 Fuente de verdad
⚠️ **Nunca se utiliza `ks.porcentaje` como valor oficial**.  
Este campo solo se conserva con fines comparativos o históricos.

---

## 7. Ordenamiento (Ranking de responsables)

El orden define todas las tablas y rankings.

### 7.1 Prioridad de orden

1. `compromiso_pct` DESC
2. `fallas` ASC
3. `(OK + Fallas)` DESC (mayor volumen primero)
4. `nombre_completo` ASC

Esto garantiza que:
- El mejor desempeño aparece arriba
- A igualdad de compromiso, gana quien falla menos
- A igualdad total, se prioriza mayor carga de trabajo

---

## 8. Indicadores globales

### 8.1 Compromiso global

```
Compromiso Global = SUM(OK) / SUM(OK + Fallas) * 100
```

Representa el desempeño **real agregado**, no un promedio simple.

---

### 8.2 Promedio simple

```
Promedio Simple = PROMEDIO(compromiso_pct por gerente)
```

- Cada gerente pesa igual
- Útil para comparaciones individuales

---

### 8.3 Promedio ponderado

```
Promedio Ponderado = SUM(compromiso_pct × (OK + Fallas)) / SUM(OK + Fallas)
```

- Da mayor peso a quien tiene más tareas
- Reduce distorsión por bajo volumen

---

## 9. Clasificaciones especiales

### 9.1 Top Mejores
- Primeros `N` responsables tras el ordenamiento
- Por defecto: `N = 5`

### 9.2 Top Peores
- Últimos `N` responsables
- Se obtiene invirtiendo el ranking

---

## 10. Reglas visuales (badges)

### 10.1 Colores por estado

| Condición | Clase | Significado |
|--------|------|------------|
| Fallas > 0 | `risk` | Riesgo |
| Fallas = 0 y % < 100 | `warn` | Advertencia |
| Fallas = 0 y % = 100 | `ok` | Óptimo |

Estas reglas aplican a:
- Conteo de fallas
- Porcentaje de compromiso

---

## 11. Estructura del reporte HTML

Secciones:
1. **Encabezado** (fechas, generación, fórmula)
2. **KPIs globales**
3. **Top Mejores**
4. **Top Peores**
5. **Detalle completo (ranking)**

El diseño es:
- Responsive
- Compatible con clientes de correo
- Sin dependencias externas

---

## 12. Envío de correo

### 12.1 Asunto

```
📊 Reporte KPI Semanal - {semana_inicio} al {semana_fin}
```

### 12.2 Destinatarios

Lista fija:
- desarrollo@progel.com.mx
- gerentecapitalhumano@progel.com.mx

### 12.3 Motor de envío

- Clase: `MailSender`
- Método:
  ```php
  sendMail($subject, $body, $to)
  ```

---

## 13. Manejo de errores

El script detiene ejecución cuando:
- No hay snapshots
- El último snapshot no tiene filas
- Falla la preparación de queries

Mensajes:
- Se devuelven como HTML plano o texto
- Código HTTP 500 cuando aplica

---

## 14. Suposiciones y restricciones

- Un snapshot representa **una foto inmutable** de la semana
- El cálculo siempre se hace sobre **un solo `generado_en`**
- El archivo **no modifica datos**, solo consulta y presenta

---

## 15. Consideraciones futuras

- Parametrizar `empresaId`
- Parametrizar rol objetivo
- Históricos comparativos (semana vs semana)
- Adjuntar PDF
- Envío segmentado por área

---

## 16. Resumen ejecutivo

Este archivo define un **estándar único, reproducible y auditable** para medir el compromiso semanal.  
La métrica es clara, consistente y resistente a manipulaciones, ya que se **recalcula desde datos crudos** y se presenta con reglas visuales y de ranking bien definidas.

