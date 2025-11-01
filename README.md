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
3. **Las dependencias apuntan hacia dentro**: la capa de fuera (Presentación) conoce a la de dentro (Aplicación), pero **el Dominio no conoce la infraestructura**. Esto es clave.
4. **Los repositorios son interfaces en el dominio**, y las implementaciones concretas (hoy JSON, mañana SQLite/MySQL) están en Infraestructura.
5. **Los eventos de dominio** se publican sin saber quién los va a escuchar (EventBus en memoria): esto muestra **desacoplamiento**.

En limpio, la app queda así:

```text
Presentation (public/, src/Controllers)
      ↓
Application (UseCases, servicios de aplicación)
      ↓
Domain (Entidades, Repositorios, Eventos)
      ↓
Infrastructure (JSON, EventBus, próximamente SQLite)
```

- **Presentation**: solo orquesta requests/responses.
- **Application**: usa el dominio para hacer cosas concretas.
- **Domain**: conoce las reglas de negocio.
- **Infrastructure**: sabe “cómo” se guardan las cosas.

👉 Eso es lo que hace que puedas mover de JSON a SQLite **sin tocar** `Album.php` o `Hero.php`. Eso es Clean.

---

## 2. Buenas prácticas que ejecuta este proyecto

- ✅ **Front Controller único** (`public/index.php`): toda la app entra por ahí.
- ✅ **Código de negocio fuera de `public/`**: nada de “controladores sueltos” en la carpeta pública.
- ✅ **PSR-4 / Autoload con Composer**: namespaces bajo `Src\` mapeados a `src/`.
- ✅ **Inyección de dependencias centralizada** en `src/bootstrap.php`: ahí se “arma” la app y se deciden las implementaciones reales.
- ✅ **Repositorios desacoplados**: el dominio define interfaces; Infra las implementa.
- ✅ **EventBus en memoria**: cuando pasa algo (crear álbum, héroe…), se publica un evento → limpio, extensible.
- ✅ **Tests con PHPUnit**: `vendor/bin/phpunit --testdox`.
- ✅ **Análisis estático con PHPStan**: para mantener calidad.
- ✅ **Tasks de VS Code**: para automatizar servidor, tests, push, etc.

Esto demuestra que no es solo “un PHP con carpetas”, sino un **ejercicio de arquitectura**.

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
│   ├── bootstrap.php       # Inyección de dependencias (contendor casero)
│   ├── Controllers/        # Controladores HTTP
│   ├── Albums/             # Módulo de Álbumes (Domain + Application + Infra del módulo)
│   ├── Heroes/             # Módulo de Héroes
│   ├── Notifications/      # Módulo de notificaciones/eventos
│   └── Shared/             # Router, EventBus, helpers compartidos
│
├── storage/                # Persistencia JSON (MVP, se puede cambiar por DB)
├── tests/                  # PHPUnit
├── composer.json           # Dependencias y autoload PSR-4
├── phpunit.xml.dist
└── .env.example            # Ejemplo de variables de entorno (NO se sube el real)
```

---

## 4. Requisitos

- PHP **8.2** o superior  
- **Composer** instalado  
- Extensiones PHP típicas (`json`, `mbstring`, `pdo` si vas a usar DB)  
- (Opcional) VS Code con tasks  
- (Opcional) Servidor embebido de PHP

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

# 4. Levantar el servidor
php -S localhost:8080 -t public

# 5. Abrir en el navegador
http://localhost:8080/
```

👉 **IMPORTANTE**  
- La carpeta **`vendor/` NO se sube a Git** (se regenera con `composer install`).  
- El archivo **`.env` TAMPOCO se sube a Git** (contiene datos sensibles).

---

## 6. Por qué `vendor/` no se sube

`vendor/` contiene todas las dependencias externas instaladas con Composer.  
Subirla haría el repositorio innecesariamente pesado.  
Por buenas prácticas, solo se versionan:

- `composer.json` → lista de dependencias.  
- `composer.lock` → versiones exactas instaladas.

Ejecutando `composer install` en cualquier entorno se regenerará `vendor/` igual que en el original.

```bash
> 💡 Nota: la carpeta `vendor/` está en .gitignore y NO se sube al repositorio.
```

---

## 7. Archivo `.env`: configuración y API keys

El archivo `.env` guarda configuraciones privadas como claves API, tokens o credenciales.  
Por seguridad **nunca debe subirse** al repositorio.

### 🧩 Ejemplo de `.env.example`

```env
APP_ENV=local
APP_DEBUG=true

# Puertos / rutas
APP_URL=http://localhost:8080

# OpenAI / IA / servicios externos
OPENAI_API_KEY=pon-aqui-tu-api-key
OPENAI_MODEL=gpt-4o-mini

# Storage
STORAGE_DRIVER=json
STORAGE_PATH=storage
```

### 📌 Cómo usarlo

1. Copiá el archivo de ejemplo:
   ```bash
   cp .env.example .env
   ```
2. Reemplazá los valores por tus claves reales.
3. Asegurate de que `.env` está incluido en `.gitignore` para no subirlo nunca.

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

- **Levantar server**: `php -S localhost:8080 -t public`  
- **Tests**: `vendor/bin/phpunit --testdox`  
- **PHPStan**: `vendor/bin/phpstan analyse --memory-limit=512M`  
- **Push estándar**: copia README → commit → push automático.

---

## 10. Roadmap técnico

- Router 100% desacoplado (`src/Shared/Http/Router.php`)
- Sustitución de JSON por **SQLite**
- Microservicio PHP para **OpenAI**
- Autenticación básica (proteger endpoints)
- CI local con tasks obligatorios

---

## Autor

**Luis Martín Pallante**  
con la ayuda de **Alfred – asistente copiloto IA**
