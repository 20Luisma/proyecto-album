# Clean Marvel Album – Arquitectura Clean en PHP 8.2

**Clean Marvel Album** es una aplicación web en **PHP 8.2** pensada como ejemplo real de **Arquitectura Limpia (Clean Architecture)** aplicada a un dominio sencillo: **álbumes y héroes de Marvel**.

El objetivo del proyecto **no es solo** mostrar una web que lista álbumes, sino enseñar **cómo estructurar un proyecto PHP moderno** para que:
- el **dominio** no dependa del framework,
- puedas **cambiar la base de datos** sin romper todo,
- puedas exponer la misma lógica por **web, API o CLI**,
- y puedas **testear** sin montar servidor.

---

## 1. ¿Por qué esto es una Arquitectura Clean?

La idea central de Clean Architecture es **proteger el core del negocio** (el dominio) de los detalles externos (web, BD, framework, UI).  
Este proyecto sigue esa idea porque:

1. **Las reglas de negocio están en `src/Albums`, `src/Heroes` y `src/Notifications`** (dominio + aplicación), NO en `public/`.
2. **La web es un detalle**: `public/index.php` solo recibe la request y la pasa al **Router** → **Controller** → **Caso de uso**.
3. **Las dependencias apuntan hacia dentro**: la capa de fuera (Presentación) conoce a la de dentro (Aplicación), pero **el Dominio no conoce la infraestructura**.
4. **Los repositorios son interfaces en el dominio**, y las implementaciones concretas (hoy JSON, mañana SQLite/MySQL) están en Infraestructura.
5. **Los eventos de dominio** se publican sin saber quién los va a escuchar (EventBus en memoria): esto muestra **desacoplamiento**.

Estructura conceptual:

```text
Presentation (public/, src/Controllers)
      ↓
Application (UseCases, servicios de aplicación)
      ↓
Domain (Entidades, Repositorios, Eventos)
      ↓
Infrastructure (JSON, EventBus, próximamente SQLite)
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
- ✅ **Tasks de VS Code personalizadas**: ejecutar servidor, tests, QA completo y subir cambios con un solo clic.  
- ✅ **Documentación técnica en `/docs`**: incluye requerimientos funcionales y diagramas UML generados durante el diseño.

Este conjunto de prácticas convierte Clean Marvel Album en una **base sólida para aprender y aplicar Arquitectura Limpia en PHP moderno**.

---

## 3. Estructura de carpetas

```text
clean-marvel/
├── public/
│   ├── assets/             # CSS, JS, UI
│   ├── uploads/            # Portadas de álbumes
│   └── index.php           # Front controller (única entrada)
│
├── src/
│   ├── bootstrap.php       # Inyección de dependencias
│   ├── Controllers/        # Controladores HTTP
│   ├── Albums/             # Módulo de Álbumes (Domain + Application + Infra)
│   ├── Heroes/             # Módulo de Héroes
│   ├── Notifications/      # Módulo de notificaciones/eventos
│   └── Shared/             # Router, EventBus, helpers compartidos
│
├── storage/                # Persistencia JSON (MVP, intercambiable por DB)
├── tests/                  # PHPUnit
├── docs/                   # Requerimientos, diagramas UML, especificaciones
├── composer.json           # Dependencias y autoload PSR-4
├── phpunit.xml.dist
└── .env.example            # Ejemplo de variables de entorno (NO se sube el real)
```

---

## 4. Requisitos

- PHP **8.2+**  
- **Composer** instalado  
- Extensiones PHP: `json`, `mbstring`, `pdo`  
- (Opcional) **VS Code** con soporte de Tasks  
- (Opcional) **Servidor embebido** de PHP

---

## 5. Instalación rápida

```bash
# 1. Clonar
git clone https://github.com/tu-usuario/clean-marvel.git
cd clean-marvel

# 2. Instalar dependencias (esto crea vendor/)
composer install

# 3. Crear el archivo .env a partir del ejemplo
cp .env.example .env

# 4. Levantar el servidor local
php -S localhost:8080 -t public

# 5. Abrir en navegador
http://localhost:8080/
```

> 💡 **Nota:**  
> - La carpeta `vendor/` **no se sube al repositorio** (se regenera con `composer install`).  
> - El archivo `.env` **tampoco se sube** (contiene claves privadas).

---

## 6. Dependencias (`vendor/`) y autoload

`vendor/` contiene todas las librerías externas instaladas por Composer.  
No se incluye en GitHub porque pesa mucho y se puede regenerar fácilmente.

Solo se suben:
- `composer.json` → dependencias declaradas  
- `composer.lock` → versiones exactas

Ejecutando `composer install` se recrea todo el entorno de dependencias idéntico.

---

## 7. Archivo `.env` – Configuración y API Keys

El archivo `.env` almacena configuraciones sensibles como claves de API (por ejemplo, la de **OpenAI**).

### 📘 Ejemplo de `.env.example`

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Claves de servicios externos
OPENAI_API_KEY=pon-aqui-tu-api-key
OPENAI_MODEL=gpt-4o-mini

# Persistencia
STORAGE_DRIVER=json
STORAGE_PATH=storage
```

### 📍 Cómo usarlo
```bash
cp .env.example .env
```
Luego edita con tus datos.  
El archivo `.env` está en `.gitignore` y **no debe subirse** nunca al repositorio.

---

## 8. Endpoints principales

| Método | Endpoint                      | Descripción                                     |
|--------|-------------------------------|-------------------------------------------------|
| `GET`  | `/albums`                     | Lista todos los álbumes creados.                |
| `POST` | `/albums`                     | Crea un nuevo álbum.                            |
| `DELETE`| `/albums/{albumId}`          | Elimina un álbum y sus héroes asociados.        |
| `GET`  | `/albums/{albumId}/heroes`    | Lista los héroes de un álbum específico.        |
| `POST` | `/albums/{albumId}/heroes`    | Añade un nuevo héroe a un álbum.                |
| `DELETE`| `/heroes/{heroId}`           | Elimina un héroe específico.                    |
| `GET`  | `/notifications`              | Obtiene el log de notificaciones.               |
| `POST` | `/comics/generate`            | Genera un cómic con IA basado en héroes.        |

---

## 9. Tasks de VS Code

El proyecto incluye **tareas automáticas** definidas en `.vscode/tasks.json` para acelerar el desarrollo:

- 🚀 **Levantar servidor local**  
- 🧪 **Ejecutar PHPUnit**  
- 🔍 **Ejecutar PHPStan**  
- 🧰 **Validar Composer**  
- ⚙️ **QA completo (tests + análisis)**  
- ⬆️ **Push estandarizado a GitHub** (sin escribir comandos)

Estas tasks permiten mantener un flujo de trabajo limpio, automatizado y reproducible entre desarrolladores.

---

## 10. Documentación y diagramas (`/docs`)

En la carpeta `/docs` se incluyen todos los **documentos técnicos** relacionados con el proyecto:
- Requerimientos funcionales y no funcionales.  
- Diagramas **UML de clases, casos de uso y componentes**.  
- Especificaciones de arquitectura y notas de diseño.

Esto facilita la comprensión del proyecto y su evolución a futuras versiones (por ejemplo, migración a SQLite o microservicios).

---

## 11. Roadmap técnico

- Router dedicado (`src/Shared/Http/Router.php`)  
- Sustitución de JSON por **SQLite**  
- Microservicio PHP para **OpenAI**  
- Autenticación básica  
- CI local con tasks obligatorios

---

## Autor

**Luis Martín Pallante**  
con la ayuda de **Alfred – asistente copiloto IA**
