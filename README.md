# UNFV School

Sistema web de gestion escolar desarrollado en Laravel para administrar alumnos, apoderados, docentes, matriculas, pagos, moras, comunicados y evaluaciones docentes.

El proyecto esta orientado a una institucion de nivel Inicial y Primaria, con secciones simples `A`, `B` y `C`, roles diferenciados y flujos administrativos para secretaria y administracion.

## Estado Actual

- Framework: Laravel 13.
- PHP requerido: 8.3 o superior.
- Frontend: Blade, Tailwind CSS y Vite.
- Base de datos: compatible con PostgreSQL y SQLite para pruebas.
- Pruebas: PHPUnit con base SQLite en memoria.


## Modulos Principales

- Autenticacion por roles: administrador, secretaria, docente, alumno y apoderado.
- Dashboard con indicadores generales.
- Gestion de usuarios.
- Gestion de alumnos, apoderados y docentes.
- Matriculas por anio academico, nivel, grado y seccion.
- Asignacion docente por curso, grado, seccion y anio academico.
- Conceptos de pago para matricula y mensualidades.
- Pagos de alumnos con monto tomado automaticamente desde el concepto de pago.
- Configuracion y aplicacion de moras.
- Bloqueo/desbloqueo de derecho a examen segun pagos vencidos.
- Comunicados generales, academicos, de pagos y de mora.
- Evaluacion de docentes por alumnos y apoderados.
- Reportes para administracion y docentes.

## Roles y Accesos

El seeder crea usuarios demo con la clave `password`:

| Rol | Email |
| --- | --- |
| Administrador | `admin@school.test` |
| Secretaria | `secretaria@school.test` |
| Alumno | `alumno@school.test` |
| Apoderado | `apoderado@school.test` |
| Docente | `docente@school.test` |

## Instalacion

1. Instalar dependencias PHP:

```bash
composer install
```

2. Instalar dependencias frontend:

```bash
npm install
```

3. Crear archivo de entorno:

```bash
cp .env.example .env
php artisan key:generate
```

4. Configurar PostgreSQL en `.env`.

El proyecto esta preparado principalmente para PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=unfv_school
DB_USERNAME=postgres
DB_PASSWORD=
```

Ajusta `DB_USERNAME` y `DB_PASSWORD` segun tu instalacion local. SQLite se usa solo para pruebas automatizadas en memoria mediante `phpunit.xml`.

5. Ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

6. Compilar assets:

```bash
npm run build
```

7. Levantar el servidor:

```bash
php artisan serve
```

Tambien puedes usar el script de desarrollo:

```bash
composer run dev
```

## Comandos Utiles

Ejecutar pruebas:

```bash
php artisan test
```

Aplicar moras manualmente:

```bash
php artisan payments:apply-late-fees
```

Ejecutar el scheduler de Laravel:

```bash
php artisan schedule:work
```

El scheduler ejecuta diariamente el comando `payments:apply-late-fees`.

## Base de Datos

Tablas principales:

- `users`: cuentas y roles del sistema.
- `academic_years`: anios academicos.
- `levels`: niveles escolares.
- `grades`: grados por nivel.
- `courses`: cursos.
- `students`: alumnos.
- `guardians`: apoderados.
- `student_guardian`: relacion alumno-apoderado.
- `teachers`: docentes.
- `enrollments`: matriculas por anio, grado y seccion.
- `teacher_assignments`: asignaciones de docentes.
- `payment_concepts`: conceptos de pago.
- `student_payments`: pagos generados o registrados para alumnos.
- `late_fee_settings`: reglas de mora.
- `announcements`: comunicados.
- `announcement_recipients`: destinatarios y lectura de comunicados.
- `evaluation_periods`: periodos de evaluacion.
- `evaluation_criteria`: criterios de evaluacion.
- `teacher_evaluations`: evaluaciones docentes.
- `evaluation_details`: detalle de puntajes.


## Flujo de Pagos

1. Administracion crea conceptos de pago en `Conceptos de pago`.
2. Cada concepto define tipo, nombre, monto, mes y fecha de vencimiento.
3. Al registrar una mensualidad/pago para un alumno, se selecciona el concepto.
4. El sistema toma automaticamente el monto y vencimiento desde `payment_concepts`.
5. El campo `Monto pagado` se usa solo para registrar abonos o pagos completos.
6. Las moras se aplican segun `late_fee_settings`.
7. Si corresponde, el sistema bloquea derecho a examen y genera comunicado de mora.

## Pruebas

La suite actual cubre:

- Accesos por rol.
- Matricula y generacion de pagos.
- Gestion de conceptos de pago.
- Visualizacion de pagos por apoderado.
- Registro de pagos sin monto manual.
- Aplicacion de moras.
- Generacion de comunicados de mora.
- Evaluacion docente.

Ultima verificacion ejecutada:

```bash
php artisan test
```

Resultado esperado actual: 23 pruebas aprobadas.

## Notas de Desarrollo

- El layout principal esta en `resources/views/components/layouts/app.blade.php`.
- La configuracion de recursos CRUD esta en `config/school.php`.
- Los servicios de negocio estan en `app/Services`.
- Las rutas web estan en `routes/web.php`.
- Las tareas programadas y comandos artisan estan en `routes/console.php`.
