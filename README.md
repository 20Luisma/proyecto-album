# Clean Marvel Album – Arquitectura Clean en PHP 8.2

**Clean Marvel Album** es una aplicación web desarrollada en **PHP 8.2** que implementa los principios de **Arquitectura Limpia (Clean Architecture)** y **Diseño Guiado por el Dominio (DDD)**. El sistema permite gestionar álbumes de cromos de Marvel y añadir héroes a ellos, sirviendo como un proyecto de referencia para construir software mantenible, escalable y desacoplado.

---

## Arquitectura

El proyecto sigue una estricta separación de capas, garantizando que la lógica de negocio (dominio) sea independiente de la infraestructura y la presentación.

```
┌──────────────────┐
│  Presentation    │ (index.php, views/*.php)
└────────┬─────────┘
         │
┌────────▼────────┐
│  Application     │ (Use Cases, DTOs)
└────────┬─────────┘
         │
┌────────▼────────┐
│  Domain          │ (Entities, Repositories, Events)
└────────┬─────────┘
         │
┌────────▼────────┐
│  Infrastructure  │ (Persistence, EventBus Impl.)
└──────────────────┘
```

### Componentes Clave

- **Capas (Domain, Application, Infrastructure)**: Cada módulo (`Albums`, `Heroes`, `Notifications`) está organizado internamente siguiendo esta estructura.
- **EventBus In-Memory**: Un bus de eventos síncrono (`InMemoryEventBus`) desacopla la lógica de negocio de los efectos secundarios. Por ejemplo, al crear un héroe, se publica un `HeroCreated` que es capturado por un manejador de notificaciones.
- **Persistencia en JSON**: Como prueba de concepto (MVP), la persistencia se implementa con archivos JSON. El diseño permite un reemplazo sencillo a un motor como **SQLite** o **MySQL** con solo implementar una nueva clase de repositorio.
- **Inyección de Dependencias**: El `bootstrap.php` centraliza el "cableado" de dependencias, facilitando la gestión y el intercambio de implementaciones.

### Estructura de Carpetas

```
clean-marvel/
├── public/
│   ├── assets/             # CSS y JS modular (ES Modules) para la UI
│   └── index.php           # Front controller y router HTTP (HTML + JSON)
│
├── src/
│   ├── bootstrap.php       # Inyección de dependencias
│   ├── Albums/             # Módulo de Álbumes (Domain, App, Infra)
│   ├── Heroes/             # Módulo de Héroes (Domain, App, Infra)
│   ├── Notifications/      # Módulo de Notificaciones
│   └── Shared/             # Componentes compartidos (EventBus, JsonResponse)
│
├── storage/
│   ├── albums.json         # Base de datos de álbumes
│   ├── heroes.json         # Base de datos de héroes
│   └── notifications.log   # Log de notificaciones (usado por la UI)
│
├── tests/
│   ├── Doubles/            # Repositorios "dobles" para tests
│   └── ...                 # Tests unitarios y de aplicación
│
├── .env                    # Archivo de configuración (no versionado)
├── composer.json           # Dependencias PHP
└── phpunit.xml.dist        # Configuración de PHPUnit
```

---

## Endpoints de la API

La API REST gestiona todos los recursos del sistema y es consumida por las interfaces de usuario.

| Método | Endpoint                      | Descripción                                     |
|--------|-------------------------------|-------------------------------------------------|
| `GET`  | `/albums`                     | Lista todos los álbumes creados.                |
| `POST` | `/albums`                     | Crea un nuevo álbum.                            |
| `DELETE`| `/albums/{albumId}`           | Elimina un álbum y todos sus héroes asociados.  |
| `GET`  | `/albums/{albumId}/heroes`    | Lista los héroes de un álbum específico.        |
| `POST` | `/albums/{albumId}/heroes`    | Añade un nuevo héroe a un álbum.                |
| `DELETE`| `/heroes/{heroId}`            | Elimina un héroe específico.                    |
| `GET`  | `/notifications`              | Obtiene el log de notificaciones en tiempo real.|
| `POST` | `/comics/generate`            | Genera un cómic IA con héroes seleccionados.    |
| `POST` | `/dev/tests/run`              | Ejecuta PHPUnit (solo entorno local).           |
| `DELETE`| `/notifications`             | Limpia el log de notificaciones.                |

---

## Interfaces de Usuario (UI)

Las vistas se renderizan desde `public/index.php` usando las plantillas PHP ubicadas en `views/`. Cada vista carga módulos JS desde `public/assets/` que consumen la API.

### `/albums`
- **Crear álbumes**: Formulario para añadir nuevos álbumes y subir portadas (URL o archivo).
- **Listar, editar y eliminar álbumes**: Grid con filtros/orden y acciones de borrado. Incluye panel de actividad y ejecución de tests (`/dev/tests/run`).
- **Acceso a héroes**: Navega a `/heroes` con parámetros del álbum seleccionado.

### `/heroes`
- **Crear héroes**: Formulario asociado al álbum recibido por query string (`albumId`, `albumName`).
- **Listar, editar y eliminar héroes**: Tarjetas con modo edición inline y registro de actividad local.
- **Notificaciones en vivo**: Muestra actividad basada en eventos publicados y almacenados en `notifications.log`.

### `/comic`
- **Selección de héroes global** para generar cómics con IA.
- **Generación de historia** mediante `POST /comics/generate`, mostrando viñetas y slideshow con los héroes elegidos.

---

## Tests

El proyecto utiliza **PHPUnit** para garantizar la calidad y el correcto funcionamiento de la lógica de negocio y la infraestructura.

### Ejecutar Tests

Para correr la suite de tests, ejecuta:
```bash
composer test
```

El resultado esperado es:
```
OK (10 tests, 25 assertions)
```

---

## Ejecución en Local

Sigue estos pasos para levantar el proyecto en tu máquina.

**1. Instalar dependencias:**
```bash
composer install
```

**2. Iniciar el servidor web de PHP:**
```bash
php -S localhost:8080 -t public
```

**3. Acceder a la aplicación:**
Abre tu navegador y visita [http://localhost:8080/](http://localhost:8080/) (o directamente `/albums`, `/heroes`, `/comic`).

---

## Comandos Útiles

### Limpiar Datos
Si necesitas empezar desde cero, puedes borrar los archivos de almacenamiento con este comando:
```bash
rm storage/*.json storage/notifications.log
```

---

## Autor

**Luis Martín Pallante**
- **Sitio Web**: [CreaWebes.com](https://www.creawebes.com)
- **Perfil**: Desarrollador Full Stack y Arquitecto de Software.
