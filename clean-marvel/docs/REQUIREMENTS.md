# 📋 Requerimientos del Proyecto — Clean Marvel Album

## 1. Descripción general

**Clean Marvel Album** es una aplicación PHP 8.2 que implementa los principios de **Arquitectura Limpia (Clean Architecture)**, **DDD ligero** y **buenas prácticas de desacoplamiento**.  
Permite gestionar álbumes y héroes del universo Marvel y sirve como proyecto base para aplicar arquitectura limpia y pruebas automatizadas en PHP moderno.

---

## 2. Requerimientos funcionales

### Álbumes
- RF-01: Crear álbum con nombre y portada.
- RF-02: Listar todos los álbumes.
- RF-03: Editar nombre o portada.
- RF-04: Eliminar álbum y sus héroes asociados.
- RF-05: Guardar datos en JSON o base de datos.

### Héroes
- RF-06: Agregar héroes a un álbum específico.
- RF-07: Listar héroes por álbum.
- RF-08: Editar o eliminar héroes.
- RF-09: Cada héroe tiene nombre, slug, descripción e imagen.

### Notificaciones
- RF-10: Registrar eventos de creación/edición/eliminación en `notifications.log`.
- RF-11: Endpoint `/notifications` devuelve el log en tiempo real.

### Cómic generado por IA
- RF-12: Generar cómic combinando héroes seleccionados.
- RF-13: Integrar con OpenAI para generar contenido IA.

### Administración
- RF-14: Servicio `SeedHeroesService` para sembrar héroes de prueba.
- RF-15: Endpoint `/dev/tests/run` ejecuta PHPUnit desde la UI.

---

## 3. Requerimientos no funcionales

| Tipo | Descripción |
|------|--------------|
| Rendimiento | Las operaciones deben ejecutarse en menos de 200 ms en entorno local. |
| Mantenibilidad | El código sigue principios SOLID y Clean Architecture. |
| Pruebas | Cobertura mínima del 80% con PHPUnit. |
| Seguridad | Endpoints administrativos protegidos en producción. |
| Escalabilidad | Capa de infraestructura puede migrar de JSON a SQLite/MySQL. |
| Observabilidad | Manejo de errores unificado con `JsonResponse::error()`. |

---

## 4. Requerimientos técnicos

| Área | Especificación |
|------|----------------|
| Lenguaje | PHP ≥ 8.2 |
| Servidor | PHP Built-in Server (localhost:8080) |
| Framework | Arquitectura propia (sin frameworks externos) |
| Testing | PHPUnit 10.5.x |
| Análisis estático | PHPStan |
| Autoload | PSR-4 (`Src\` → `src/`) |
| Persistencia | Archivos JSON (MVP), futura SQLite/MySQL |
| Bus de eventos | InMemoryEventBus |
| Controladores | `src/Controllers/` |
| Entry point | `public/index.php` |
| Contenedor | `src/bootstrap.php` |

---

## 5. Entorno de desarrollo

- **Editor:** Visual Studio Code  
- **Tareas automatizadas:** `.vscode/tasks.json`
  - Servidor PHP embebido  
  - PHPUnit (testdox + colores)  
  - PHPStan (análisis)  
  - Composer validate  
  - QA completo (tests + phpstan + composer)  
  - Git automatizado (add + commit + push + sync README)

---

## 6. Dependencias externas

| Tipo | Paquete | Versión | Descripción |
|------|----------|----------|--------------|
| Core | `php` | ^8.2 | Lenguaje base |
| Testing | `phpunit/phpunit` | ^10.5 | Framework de testing |
| Static Analysis | `phpstan/phpstan` | ^1.10 | Análisis estático |
| Autoload | `composer` | latest | Manejador de dependencias |
| IA (opcional) | `openai-php/client` | latest | Generación IA para cómics |

---

## 7. Roadmap técnico

- 🔜 Router dedicado en `src/Shared/Http/Router.php`
- 🔜 Microservicio OpenAI separado del core
- 🔜 Sistema RAG (vectorización y recuperación contextual)
- 🔜 Login y roles para endpoints administrativos
- 🔜 Migración JSON → SQLite/MySQL sin romper dominio
- 🔜 CI local automatizado con tasks VS Code

---

## 8. Autores

**Luis Martín Palllante & Alfred – Asistente copiloto IA**  
CreaWebes · Proyecto educativo basado en Clean Architecture y PHP 8.2
