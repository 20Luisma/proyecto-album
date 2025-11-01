# Clean Marvel Album – Arquitectura Clean en PHP 8.2

**Clean Marvel Album** es una aplicación web en **PHP 8.2** pensada como ejemplo real de **Arquitectura Limpia (Clean Architecture)** aplicada a un dominio sencillo: **álbumes y héroes de Marvel**, con la novedad de que ahora incorpora **un microservicio separado para hablar con OpenAI**.

El objetivo del proyecto **no es solo** mostrar una web que lista álbumes o genera cómics, sino enseñar **cómo estructurar un proyecto PHP moderno** para que:

- el **dominio** no dependa del framework,
- puedas **cambiar la capa de persistencia** (JSON hoy, SQLite/MySQL mañana) sin romper todo,
- puedas exponer la misma lógica por **web, API o CLI**,
- y puedas **testear** sin montar servidor,
- además de **conectarte a un servicio externo (OpenAI) sin acoplar tu dominio**.

---

## 1. ¿Por qué esto es una Arquitectura Clean?

La idea central de Clean Architecture es **proteger el core del negocio** de los detalles externos (web, BD, framework, UI).  
Este proyecto sigue esa idea porque:

1. **Las reglas de negocio están en `src/Albums`, `src/Heroes` y `src/Notifications`** (dominio + aplicación), NO en `public/`.
2. **La web es un detalle**: `public/index.php` solo recibe la request y la pasa al **Router** → **Controller** → **Caso de uso**.
3. **Las dependencias apuntan hacia dentro**: la capa de fuera (Presentación) conoce a la de dentro (Aplicación), pero **el Dominio no conoce la infraestructura**.
4. **Los repositorios son interfaces en el dominio**, y las implementaciones concretas (hoy JSON, mañana SQLite/MySQL) están en Infraestructura.
5. **Los eventos de dominio** se publican sin saber quién los va a escuchar (EventBus en memoria): esto muestra **desacoplamiento**.
6. **La integración con OpenAI está aislada en `openai-service/`**: la app principal no “sabe” cómo se habla con OpenAI, solo sabe que hay un endpoint HTTP que le devuelve una historia.

Estructura conceptual:

```text
Presentation (public/, src/Controllers)
      ↓
Application (UseCases, servicios de aplicación)
      ↓
Domain (Entidades, Repositorios, Eventos)
      ↓
Infrastructure (JSON, EventBus, próximamente SQLite)
      ↓
External Services (microservicio OpenAI, otros)
```

👉 Esto permite cambiar tecnologías sin romper el núcleo de negocio.

---

## 2. Buenas prácticas que ejecuta este proyecto

- ✅ **Front Controller único** (`public/index.php`): toda la app entra por ahí.  
- ✅ **Código de negocio fuera de `public/`**: separación clara de responsabilidades.  
- ✅ **PSR-4 / Autoload con Composer**: namespaces bajo `Src\` mapeados a `src/`.  
- ✅ **Inyección de dependencias centralizada** (`src/bootstrap.php`).  
- ✅ **Repositorios desacoplados**: dominio define interfaces, infraestructura implementa.  
- ✅ **EventBus en memoria** para comunicar módulos sin dependencias directas.  
- ✅ **Tests con PHPUnit** y **análisis estático con PHPStan**.  
- ✅ **Tasks de VS Code personalizadas** (ver más abajo): ejecutar servidor, levantar microservicio, QA completo y subir cambios con un solo clic.  
- ✅ **Documentación técnica en `/docs`**: incluye requerimientos funcionales y diagramas UML generados durante el diseño.  
- ✅ **Microservicio externo (`openai-service/`)** para no mezclar lógica de IA con lógica de dominio.

Este conjunto de prácticas convierte Clean Marvel Album en una **base sólida para aprender y aplicar Arquitectura Limpia en PHP moderno**.

---

## 3. Estructura de carpetas

```text
clean-marvel/
├── public/
│   └── index.php              # Front controller (única entrada) → http://localhost:8080
│
├── src/
│   ├── bootstrap.php          # Inyección de dependencias
│   ├── Controllers/           # Controladores HTTP
│   ├── Albums/                # Módulo de Álbumes (Domain + Application + Infra)
│   ├── Heroes/                # Módulo de Héroes
│   ├── Notifications/         # Módulo de notificaciones/eventos
│   └── Shared/                # Router, EventBus, helpers compartidos
│
├── openai-service/            # ⬅️ NUEVO: microservicio PHP separado (8081)
│   ├── public/                # punto de entrada del microservicio → http://localhost:8081
│   └── src/                   # Router, Controller y servicio que llama a OpenAI
│
├── docs/                      # Requerimientos, diagramas UML, especificaciones
├── tests/                     # PHPUnit
├── .vscode/                   # Tasks de VS Code (servidores + QA + git)
├── composer.json              # Dependencias y autoload PSR-4
├── composer.lock
└── .env.example               # Ejemplo de variables de entorno (NO se sube el real)
```

---

## 4. Microservicio OpenAI (8081)

Para no acoplar la app principal a la API de OpenAI, se creó un microservicio ligero en `openai-service/`.

### 🔗 Endpoint expuesto

**POST** `http://localhost:8081/v1/chat`

```json
{
  "messages": [
    { "role": "system", "content": "Eres un narrador de cómics de Marvel. Responde en español." },
    { "role": "user", "content": "Genera una escena con Iron Man y Capitán América." }
  ]
}
```

### ✅ Respuesta esperada

```json
{
  "ok": true,
  "content": "Iron Man sobrevolaba el cielo de Nueva York cuando..."
}
```

- Si hay un error (falta API key, OpenAI no responde, etc.), el microservicio devuelve:
  ```json
  { "ok": false, "error": "⚠️ No se ha configurado OPENAI_API_KEY en el entorno." }
  ```
- El microservicio carga el `.env` **manualmente** con `putenv()` al iniciar (no depende de phpdotenv).
- El `.env` está en `.gitignore` y **no se sube**.

### 🚀 Cómo levantarlo

```bash
cd clean-marvel/openai-service
php -S localhost:8081 -t public
```

Debe estar **levantado** antes de darle a “Generar cómic” en la app principal.

---

## 5. Requisitos

- PHP **8.2+**  
- **Composer** instalado  
- Extensiones PHP: `json`, `mbstring`, `curl`  
- (Opcional) **VS Code** con soporte de Tasks  

Para ver los comandos detallados de instalación, dependencias y despliegue, consulta **`/docs/requirements.md`**.

---

## 6. Instalación rápida

```bash
# 1. Clonar
git clone https://github.com/tu-usuario/clean-marvel.git
cd clean-marvel

# 2. Instalar dependencias
composer install

# 3. (Opcional) configurar .env del microservicio
cd openai-service
cp .env.example .env
# edita .env y pon tu OPENAI_API_KEY=...
cd ..

# 4. Levantar app principal
php -S localhost:8080 -t public

# 5. Levantar microservicio OpenAI
cd openai-service
php -S localhost:8081 -t public
```

Abrir en navegador: **http://localhost:8080**

---

## 7. Endpoints principales de la app

| Método | Endpoint                          | Descripción                                 |
|--------|-----------------------------------|---------------------------------------------|
| `GET`  | `/albums`                         | Lista todos los álbumes                     |
| `POST` | `/albums`                         | Crea un álbum                               |
| `GET`  | `/albums/{albumId}/heroes`        | Lista héroes de un álbum                    |
| `POST` | `/albums/{albumId}/heroes`        | Añade un héroe al álbum                     |
| `POST` | `/comics/generate`                | Genera cómic → llama al microservicio 8081 |

---

## 8. Tasks de VS Code (última versión)

Este proyecto ya trae en **`.vscode/tasks.json`** las tareas que estás usando ahora mismo en VS Code:

```json
{
  "version": "2.0.0",
  "tasks": [
    {
      "label": "🚀 Iniciar servidor PHP (8080)",
      "type": "shell",
      "command": "php",
      "args": ["-S", "localhost:8080", "-t", "public"],
      "options": { "cwd": "${workspaceFolder}" }
    },
    {
      "label": "🧪 Ejecutar Tests PHPUnit",
      "type": "shell",
      "command": "vendor/bin/phpunit",
      "args": ["--colors=always", "--testdox"],
      "options": { "cwd": "${workspaceFolder}" }
    },
    {
      "label": "🔍 Analizar código (PHPStan)",
      "type": "shell",
      "command": "vendor/bin/phpstan",
      "args": ["analyse", "--memory-limit=512M"],
      "options": { "cwd": "${workspaceFolder}" }
    },
    {
      "label": "⚙️ Validar composer",
      "type": "shell",
      "command": "composer",
      "args": ["validate"],
      "options": { "cwd": "${workspaceFolder}" }
    },
    {
      "label": "🧪 QA completo (tests + phpstan + composer)",
      "dependsOn": [
        "🧪 Ejecutar Tests PHPUnit",
        "🔍 Analizar código (PHPStan)",
        "⚙️ Validar composer"
      ],
      "dependsOrder": "sequence"
    },
    {
      "label": "⬆️ Git: add + commit + push (actualiza ambos README)",
      "type": "shell",
      "command": "bash",
      "args": [
        "-c",
        "cp -f clean-marvel/README.md README.md; git add -A; git commit -m \"update clean-marvel + sync README root\" || true; git push"
      ],
      "options": { "cwd": "${workspaceFolder}/.." }
    },
    {
      "label": "🧹 Git: limpiar archivos eliminados (repo raíz)",
      "type": "shell",
      "command": "bash",
      "args": [
        "-c",
        "git add -u; git commit -m \"remove deleted files\" || true; git push"
      ],
      "options": { "cwd": "${workspaceFolder}/.." }
    },
    {
      "label": "🤖 Run OpenAI Service (8081)",
      "type": "shell",
      "command": "php",
      "args": ["-S", "localhost:8081", "-t", "public"],
      "options": { "cwd": "${workspaceFolder}/openai-service" },
      "isBackground": true
    },
    {
      "label": "▶️ Run Both (8080 + 8081)",
      "dependsOn": [
        "🚀 Iniciar servidor PHP (8080)",
        "🤖 Run OpenAI Service (8081)"
      ],
      "dependsOrder": "parallel",
      "group": { "kind": "build", "isDefault": true }
    }
  ]
}
```

👉 Esto deja clarísimo para cualquiera que clone el repo **cómo levantar los dos servidores** y **qué tareas de QA tiene el proyecto**.

---

## 9. Documentación y requerimientos

La especificación más técnica (versiones exactas, cómo levantar hosts, notas de Composer, rutas de pruebas, ejemplos de `curl`) está en:

➡️ **`/docs/requirements.md`**

Así el README queda más limpio y quien quiera profundizar va al doc técnico.

---

## 👤 Autor

Proyecto desarrollado por **Martín Pallante**,  
con la colaboración de **Alfred**, asistente de IA 🤖  

[🌐 Creawebes](https://www.creawebes.com) · © 2025  

> “Diseñando tecnología limpia, modular y con propósito.”
