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

## Despliegue en Aiven
1. Ejecuta el DDL del esquema (docentes, estudiantes, materias, tutorias).
2. Carga los datos semilla de prueba.

## Despliegue en Railway
1. Conecta el repo. Railway detecta el Dockerfile.
2. Define las variables del entorno en el panel de Variables.
3. Para el certificado, elige una opción.
   - Sube `certs/ca.pem` al repo, queda fuera del docroot por seguridad.
   - O pega su contenido en la variable `DB_SSL_CA`, el código lo escribe a un temporal.
