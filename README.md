# Clean Marvel Album – Arquitectura Clean en PHP 8.2

**Clean Marvel Album** es una aplicación web desarrollada en **PHP 8.2** que implementa los principios de **Arquitectura Limpia (Clean Architecture)**, **DDD ligero** y **buenas prácticas de desacoplamiento**.  
Su objetivo no es solo gestionar álbumes y héroes de Marvel, sino servir como **proyecto de referencia** para aplicar una arquitectura clara, módulos aislados y pruebas automatizadas en PHP moderno.

---

## 1. Arquitectura Clean aplicada

La app está organizada en capas claramente separadas, de fuera hacia dentro:

```text
Presentation (public/, src/Controllers)  →  Application (UseCases)  →  Domain (Entities, Repos)  →  Infrastructure (JSON, EventBus)
```

- **Capa de Presentación**  
  - `public/index.php` actúa como **Front Controller**.  
  - `src/Controllers/*` contiene los **controladores HTTP** que orquestan la request (no contienen lógica de negocio).  
  - `PageController` atiende las rutas HTML visibles en navegador.
  - El enrutado se está moviendo progresivamente a un **Router dedicado** (`src/Shared/Http/Router.php`) para que `index.php` quede muy delgado.

- **Capa de Aplicación**  
  - Contiene los **casos de uso (Use Cases)**: crear álbum, listar, actualizar portada, crear héroe, borrar héroe, limpiar notificaciones, etc.  
  - Aquí vive la **orquestación** de dominio, no la lógica de presentación.  
  - Publica eventos de dominio cuando algo relevante ocurre (por ejemplo, “álbum actualizado”).

- **Capa de Dominio**  
  - Entidades ricas (`Album`, `Hero`) con sus invariantes.  
  - Interfaces de repositorio (pueden tener implementación en JSON hoy y en SQLite mañana).  
  - **Eventos de dominio** que luego escucha la capa superior de notificaciones.

- **Capa de Infraestructura**  
  - Repositorios que leen/escriben en JSON (`storage/*.json`).  
  - `InMemoryEventBus` para no acoplar el dominio a la infraestructura.  
  - Aquí es donde en el futuro se enchufará SQLite/MySQL sin tocar la capa de dominio.

Esta separación permite:
1. **Probar el dominio sin servidor web.**
2. **Cambiar la persistencia sin tocar el dominio.**
3. **Exponer la misma lógica vía API, CLI o Web sin duplicar código.**

---

## 2. Buenas prácticas que ya implementa

- ✅ **Front Controller único** en `public/index.php`  
  No hay “PHP suelto” en el root: todo entra por `public/`.

- ✅ **Controladores fuera de `public/`**  
  Los controladores viven en `src/Controllers`, no en la carpeta pública. Esto es clave para Clean.

- ✅ **PSR-4 / Autoload**  
  En `composer.json` se usa el namespace `Src\` → `src/`, lo que permite agregar módulos sin `require_once` manuales.

- ✅ **Inyección de dependencias centralizada**  
  `src/bootstrap.php` prepara los casos de uso y las implementaciones reales. Así los controladores solo los reciben.

- ✅ **Eventos desacoplados**  
  Cuando se crea o actualiza algo, se publica un evento en un **EventBus en memoria**, y los handlers lo escuchan (por ejemplo, para notificaciones).

- ✅ **Tests automatizados con PHPUnit**  
  Hay tests de dominio, de aplicación y de infraestructura. El objetivo es que `vendor/bin/phpunit --testdox` esté SIEMPRE en verde.

- ✅ **Análisis estático con PHPStan**  
  Se ejecuta desde VS Code con task dedicado y se está normalizando el uso de constantes definidas en runtime.

- ✅ **Tareas de desarrollo automatizadas**  
  `.vscode/tasks.json` permite levantar el servidor, correr tests, analizar con PHPStan y subir a Git en 1 clic.

---

## 3. Estructura de carpetas

```text
clean-marvel/
├── public/
│   ├── assets/             # CSS, JS, UI
│   ├── uploads/            # Portadas de álbumes
│   └── index.php           # Front controller
│
├── src/
│   ├── bootstrap.php       # Inyección de dependencias
│   ├── Controllers/        # Presentation layer (HTTP)
│   ├── Albums/             # Módulo Álbumes (Domain, App, Infra)
│   ├── Heroes/             # Módulo Héroes
│   ├── Notifications/      # Módulo de notificaciones/eventos
│   └── Shared/             # Router, EventBus, helpers compartidos
│
├── storage/                # Persistencia JSON para MVP
├── tests/                  # PHPUnit
├── composer.json
└── phpunit.xml.dist
```

---

## 4. Endpoints principales

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

## 5. Automatización y Tasks de VS Code

Para no escribir siempre los mismos comandos, el proyecto tiene tareas definidas en `.vscode/tasks.json`.

### 🚀 Servidor de desarrollo
```bash
php -S localhost:8080 -t public
```

### 🧪 Tests
```bash
vendor/bin/phpunit --colors=always --testdox
```

### 🔍 PHPStan
```bash
vendor/bin/phpstan analyse --memory-limit=512M
```

### ⚙️ Composer validate
```bash
composer validate
```

### 🧪 QA completo (secuencia)
Ejecuta PHPUnit → PHPStan → Composer validate en un solo click desde VS Code.

### ⬆️ Git: add + commit + push
Task que ya tenés armado para:
1. copiar el README del proyecto al root
2. hacer `git add -A`
3. hacer `git commit -m "update clean-marvel + sync README root"`
4. hacer `git push`

Esto queda documentado para que otro dev sepa que **no es un push manual**, sino un task estandarizado.

---

## 6. Próximamente / Roadmap técnico

- 🔜 **Router dedicado en `src/Shared/Http/Router.php`**  
  Para sacar definitivamente el `switch` de `public/index.php` y dejarlo mínimo.

- 🔜 **Microservicio PHP para OpenAI**  
  Extraer la llamada a OpenAI (cómics IA) en un endpoint propio, desacoplado de la app principal.

- 🔜 **Microservicio / módulo RAG**  
  Repositorio vectorial + recuperación de héroes / álbumes para generar contenido contextual con IA.

- 🔜 **Login / autenticación básica**  
  Para no exponer los endpoints de administración (seed, tests) en producción.

- 🔜 **Migración de JSON → SQLite/MySQL**  
  Manteniendo los mismos repositorios pero con otra implementación en Infraestructura.

- 🔜 **CI local con VS Code Tasks**  
  Que el task “QA completo” sea obligatorio antes de subir.

---

## 7. Ejecución en local

```bash
composer install
composer dump-autoload
php -S localhost:8080 -t public
# abrir http://localhost:8080/
```

---

## Autor

**Luis Martín Palllante & Alfred – asistente copiloto IA**
