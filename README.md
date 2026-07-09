# Registro de Tutorías

Aplicación PHP con PDO para registrar tutorías universitarias. Alimenta dashboards de Power BI conectados por cadena directa a MySQL en Aiven.

## Estructura

```
config/database.php   Conexión PDO con SSL a Aiven
includes/Crud.php     CRUD genérico, paginación y alta grupal por sesión
includes/helpers.php  Utilidades de vista, paginador y etiquetas
public/               Docroot (index, docentes, estudiantes, tutorias, styles)
certs/ca.pem          Certificado de la CA de Aiven (no versionar)
Dockerfile            Imagen para Railway
```

## Modelo de datos y sesiones

La tabla `tutorias` conserva el grano de una fila por par sesión-estudiante. Una tutoría grupal inserta N filas que comparten docente, materia, fecha, horas, modalidad y motivo. La columna `sesion_id` agrupa esas filas. Su valor es el `id` de la primera fila del grupo, lo que garantiza unicidad sin secuencias. Una tutoría individual es el caso N igual a 1 y también recibe su propio `sesion_id`.

Con este modelo el dashboard separa dos métricas. El número de tutorías se calcula como `DISTINCTCOUNT(sesion_id)`. El número de atenciones es el conteo de filas. El estado y la satisfacción viven por fila, así un estudiante puede constar ausente en una sesión a la que otros asistieron.

## Migración obligatoria

Ejecuta este DDL una sola vez antes de usar la versión grupal.

```sql
ALTER TABLE tutorias ADD COLUMN sesion_id INT NULL;
UPDATE tutorias SET sesion_id = id WHERE sesion_id IS NULL;
CREATE INDEX idx_tutorias_sesion ON tutorias (sesion_id);
```

La primera sentencia añade la columna. La segunda asigna a cada fila existente su propio identificador de sesión, dejando las tutorías previas como sesiones individuales. La tercera acelera las agregaciones que Power BI hace sobre `sesion_id`.

## Flujo de alta de tutorías

El formulario aplica un paso previo que discrimina estudiantes por carrera y semestre, evitando cargar el listado completo. Los estudiantes filtrados aparecen como checkboxes. Desde la vista de estudiantes también se pueden marcar varios y lanzar el alta grupal, que llega al formulario con esos estudiantes ya preseleccionados. Al guardar se insertan las filas de la sesión en una transacción.

## Despliegue en Aiven

1. Ejecuta el DDL del esquema (docentes, estudiantes, materias, tutorias).
2. Aplica la migración de `sesion_id` de la sección anterior.
3. Carga los datos semilla de prueba.

## Despliegue en Railway

1. Conecta el repo. Railway detecta el Dockerfile.
2. Define las variables del entorno en el panel de Variables.
3. Para el certificado, elige una opción.
   - Sube `certs/ca.pem` al repo, queda fuera del docroot por seguridad.
   - O pega su contenido en la variable `DB_SSL_CA`, el código lo escribe a un temporal.

## Nota sobre el puerto

La imagen `php:apache` escucha en el 80. Si Railway no enruta correctamente, añade al Dockerfile la configuración para que Apache lea `${PORT}`.

```
RUN sed -i "s/Listen 80/Listen \${PORT}/" /etc/apache2/ports.conf
```
