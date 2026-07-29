# Vera AI — Plan de Trabajo de Base de Datos (Laravel)

Este archivo divide la construcción de la base de datos en tareas pequeñas, una por tabla o grupo de tablas relacionadas. Se irá marcando el progreso conforme se trabaje sesión por sesión. Cada tarea corresponde normalmente a: migración + modelo Eloquent + (seeder si aplica) + relaciones.

> Contexto completo del proyecto y del diseño de BD guardado en memoria del repositorio (`/memories/repo/proyecto-vera-ai-contexto.md` y `/memories/repo/proyecto-vera-ai-base-datos.md`).

Leyenda: `[ ]` pendiente · `[~]` en progreso · `[x]` completado

---

## Módulo 1: Usuarios, Empresas y Accesos (Multi-tenant) ✅ Backend creado (pendiente ejecutar migraciones)
- [x] `roles` — jerarquía de permisos (SUPER_ADMIN, ADMIN_PYME, ACCOUNTANT, OPERATOR)
- [x] `users` — ampliar tabla base de Laravel (role_id, full_name, is_active) vía migración adicional
- [x] `companies` — PyMEs registradas (rfc, razón social, régimen fiscal, CP, api key PAC sandbox)
- [x] `user_companies` — tabla pivote N:M usuarios/empresas con rol por empresa

Archivos generados: migraciones (roles, companies, user_companies, add_vera_fields_to_users_table), modelos (Role, Company, UserCompany, User actualizado) con relaciones Eloquent, factories, RoleSeeder (registrado en DatabaseSeeder), Form Requests con validaciones, Policies con reglas de autorización por rol, controllers con `authorizeResource`, rutas API en `routes/api.php` (`/roles`, `/companies`, `/user-companies`). **Falta ejecutar `php artisan migrate` (lo hará el usuario).**

## Módulo 2: Catálogos Oficiales del SAT (datos semilla)
- [ ] `sat_tax_regimes` + seeder
- [ ] `sat_economic_activities` + seeder
- [ ] `sat_product_services` + seeder (catálogo de 8 dígitos)
- [ ] `sat_payment_forms` + seeder
- [ ] `sat_payment_methods` + seeder
- [ ] `sat_cfdi_uses` + seeder

## Módulo 3: Perfil Fiscal y Matriz de Indispensabilidad
- [ ] `company_economic_activities` — pivote company/giro
- [ ] `indispensability_matrix` — reglas de deducibilidad por empresa y clave SAT

## Módulo 4: Ingestión de XMLs (CFDI 4.0) y Gastos
- [ ] `cfdis` — encabezado de facturas
- [ ] `cfdi_items` — desglose de conceptos por factura

## Módulo 5: Gestión Operativa (Nómina y OpEx Fijo)
- [ ] `employees` — padrón de trabajadores
- [ ] `payroll_periods` — periodos de nómina procesados
- [ ] `payroll_details` — detalle por empleado
- [ ] `fixed_expenses` — rentas, seguros, servicios (OpEx fijo)

## Módulo 6: IA, Score de Riesgo, Alertas y Consultoría
- [ ] `notifications` — centro de notificaciones/alertas
- [ ] `risk_evaluations` — histórico del medidor de riesgo (ML)
- [ ] `llm_consultant_reports` — informes ejecutivos del agente LLM

## Módulo 7: Facturación Simulada (PAC Sandbox)
- [ ] `simulated_invoices` — facturas emitidas en sandbox

## Índices de rendimiento (al final, tras crear las tablas relacionadas)
- [x] `idx_cfdis_company_date` en `cfdis(company_id, issue_date)`
- [x] `idx_cfdis_deductibility` en `cfdis(company_id, deductibility_status)`
- [x] `idx_cfdi_items_prod_service` en `cfdi_items(sat_product_service_code)`
- [x] `idx_notifications_unread` en `notifications(company_id, is_read)` WHERE is_read = FALSE

---

## Notas de trabajo
- Orden sugerido: Módulo 1 → Módulo 2 (catálogos, no dependen de nada) → Módulo 3 → Módulo 4 → Módulo 5 → Módulo 6 → Módulo 7 → Índices.
- Cada tarea se trabajará cuando el usuario indique explícitamente a qué módulo/tabla avanzar.
- Revisar el motor de BD configurado en `.env` (MySQL/PostgreSQL) antes de escribir tipos específicos como JSONB (exclusivo de Postgres; en MySQL usar `JSON`).
