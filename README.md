# Clean Marvel Album – Arquitectura Clean en PHP 8.2

**Clean Marvel Album** es una aplicación web desarrollada en **PHP 8.2** que implementa los principios de **Arquitectura Limpia (Clean Architecture)** y **Diseño Guiado por el Dominio (DDD)**.  
El sistema permite gestionar álbumes de cromos de Marvel y añadir héroes a ellos, sirviendo como un proyecto de referencia para construir software mantenible, escalable y desacoplado.

---

## Arquitectura

El proyecto sigue una estricta separación de capas, garantizando que la lógica de negocio (dominio) sea independiente de la infraestructura y la presentación.

```
┌──────────────────┐
│  Presentation     │ (index.php, Controllers, views/*.php)
└────────┬──────────┘
         │
┌────────▼────────┐
│  Application     │ (Use Cases, DTOs, Services)
└────────┬────────┘
         │
┌────────▼────────┐
│  Domain          │ (Entities, Repositories, Events)
└────────┬────────┘
         │
┌────────▼────────┐
│  Infrastructure  │ (Persistence, EventBus Impl.)
└──────────────────┘
```

### Componentes Clave

- **Capas (Domain, Application, Infrastructure)**: Cada módulo (`Albums`, `Heroes`, `Notifications`) está organizado internamente siguiendo esta estructura.
- **EventBus In-Memory**: Un bus de eventos síncrono (`InMemoryEventBus`) desacopla la lógica de negocio de los efectos secundarios.
- **Persistencia en JSON (MVP)**: Implementación simple que puede migrarse fácilmente a **SQLite** o **MySQL**.
- **Inyección de Dependencias**: El archivo `bootstrap.php` centraliza el “cableado” de dependencias.
- **Autoload PSR-4**: Configurado en `composer.json` para el namespace `Src\`, lo que permite cargar automáticamente clases dentro de `src/`.

---

## Estructura de Carpetas

```
clean-marvel/
├── public/
│   ├── assets/             # CSS y JS modular (UI)
│   ├── uploads/            # Archivos subidos
│   └── index.php           # Punto de entrada (router principal)
│
├── src/
│   ├── bootstrap.php       # Inyección de dependencias
│   ├── Controllers/        # Controladores HTTP (Presentation Layer)
│   │   ├── AlbumController.php
│   │   ├── HeroController.php
│   │   └── ComicController.php
│   ├── Albums/             # Módulo de Álbumes (Domain, App, Infra)
│   ├── Heroes/             # Módulo de Héroes (Domain, App, Infra)
│   ├── Notifications/      # Módulo de Notificaciones
│   └── Shared/             # Componentes compartidos (EventBus, Router, etc.)
│
├── storage/
│   ├── albums.json         # Base de datos de álbumes
│   ├── heroes.json         # Base de datos de héroes
│   └── notifications.log   # Log de notificaciones
│
├── tests/                  # PHPUnit tests
│   └── ...
│
├── composer.json           # Dependencias y autoload PSR-4
└── phpunit.xml.dist        # Configuración de PHPUnit
```

---

## 🧰 Automatización y Tasks de VS Code

El proyecto incluye un archivo `.vscode/tasks.json` con tareas automatizadas que facilitan desarrollo, pruebas y despliegue.

### 🚀 Servidor de desarrollo
Inicia el servidor PHP embebido:
```bash
php -S localhost:8080 -t public
```

### 🧪 Ejecución de tests PHPUnit
Corre toda la suite con colores y formato TestDox:
```bash
vendor/bin/phpunit --colors=always --testdox
```

### 🔍 Análisis estático con PHPStan
Evalúa errores de tipo y buenas prácticas:
```bash
vendor/bin/phpstan analyse --memory-limit=512M
```

### ⚙️ Validar composer.json
Verifica la configuración de dependencias:
```bash
composer validate
```

### 🧪 QA completo (tests + phpstan + composer)
Ejecuta las tres tareas anteriores en secuencia automática:
1. PHPUnit  
2. PHPStan  
3. Composer validate  

Todo desde:
```bash
⇧⌘P → Run Task → “🧪 QA completo (tests + phpstan + composer)”
```

### ⬆️ Git: add + commit + push (actualiza ambos README)
Ejecuta una subida automatizada, sincronizando el README de `clean-marvel` con el README raíz y sube cambios al repositorio.

### 🧹 Git: limpiar archivos eliminados
Detecta y elimina del repositorio cualquier archivo borrado localmente:
```bash
git add -u && git commit -m "remove deleted files" && git push
```

Estas tareas permiten desarrollar, probar y subir código a GitHub sin salir de VS Code, manteniendo la coherencia entre el proyecto local y el repositorio remoto.

---

## Autor

**Luis Martín Palllante & Alfred – Asistente copiloto IA**
