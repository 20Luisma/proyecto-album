# docs/requirements.md
## 1. Objetivo del documento
Este documento describe **los requisitos técnicos, de entorno y de ejecución** del proyecto **Clean Marvel Album** + **microservicio `openai-service`**.  
Está pensado para desarrolladores que clonen el repositorio y quieran **levantarlo en local** siguiendo las mismas convenciones que usa Martín (Creawebes).

---

## 2. Entorno soportado

- **SO recomendado:** macOS / Linux (en Windows funciona, pero cambia el arranque de tasks).
- **PHP:** **8.2 o superior**  
  - extensiones necesarias:
    - `curl` (para llamar a OpenAI desde el microservicio)
    - `json`
    - `mbstring`
    - `pdo` *(opcional – para futuras persistencias)*
- **Composer:** 2.x
- **Navegador:** cualquiera moderno (Chrome, Edge, Safari)
- **Editor recomendado:** VS Code con las siguientes extensiones:
  - PHP Intelephense
  - GitLens
  - Markdown All in One
  - *opcional:* CodeGPT / Gemini / Alfred para refactors

---

## 3. Estructura del repositorio (técnica)

```text
clean-marvel/                # raíz de la app principal
├── public/                  # punto de entrada HTTP → :8080
│   └── index.php
├── src/
│   ├── bootstrap.php        # registro de dependencias (DIC casero)
│   ├── Shared/              # Router, EventBus, helpers
│   ├── Albums/              # caso de uso + dominio de álbumes
│   ├── Heroes/              # caso de uso + dominio de héroes
│   └── Notifications/       # eventos, listeners
│
├── openai-service/          # ⬅️ microservicio PHP independiente → :8081
│   ├── public/              # index.php + carga manual de .env
│   ├── src/                 # controller + servicio OpenAI
│   ├── composer.json        # autoload PSR-4: Creawebes\OpenAI\ → src/
│   └── .env                 # API key (no se sube)
│
├── docs/                    # este archivo + diagramas
├── tests/                   # pruebas PHPUnit
├── .vscode/                 # tasks.json (servidores, QA, git)
├── composer.json
├── composer.lock
└── phpunit.xml.dist
```

**Nota:** la app principal y el microservicio tienen **composer.json separados**. Hay que hacer `composer install` en ambos si se quieren usar por separado.

---

## 4. Instalación paso a paso

### 4.1. Clonar el repo
```bash
git clone https://github.com/tu-usuario/clean-marvel.git
cd clean-marvel
```

### 4.2. Instalar dependencias del proyecto principal
```bash
composer install
```

### 4.3. Instalar dependencias del microservicio
```bash
cd openai-service
composer install
cd ..
```

### 4.4. Crear archivo .env para el microservicio
Dentro de `openai-service/` debe existir un `.env` con la clave de OpenAI:

```env
OPENAI_API_KEY=sk-pon-aqui-tu-api-key-real
OPENAI_MODEL=gpt-4o-mini
```

- Este archivo **NO se sube a GitHub** (está en `.gitignore`).
- El microservicio ya tiene código en `public/index.php` para **cargar este .env manualmente con `putenv()`**. No hace falta phpdotenv.

---

## 5. Levantar los servidores

### 5.1. Servidor de la app principal (8080)

Desde la raíz del proyecto (`clean-marvel/`):

```bash
php -S localhost:8080 -t public
```

Esto sirve el front + backend (router) que usa la app para manejar álbumes y héroes.

Se accede en: **http://localhost:8080**

---

### 5.2. Servidor del microservicio (8081)

En una segunda terminal:

```bash
cd openai-service
php -S localhost:8081 -t public
```

Se accede en: **http://localhost:8081**  
El endpoint que usa la app principal es: **`POST http://localhost:8081/v1/chat`**

**IMPORTANTE:** si el microservicio no está levantado, la app principal devolverá `502 Bad Gateway` o el frontend mostrará: **“La IA devolvió una estructura inesperada”.**

---

### 5.3. Levantar ambos con VS Code

El repo ya trae `.vscode/tasks.json` con estas tareas:

- `🚀 Iniciar servidor PHP (8080)` → app principal
- `🤖 Run OpenAI Service (8081)` → microservicio
- `▶️ Run Both (8080 + 8081)` → **este es el que usarás siempre**

Para ejecutarlo:
1. **Cmd+Shift+P** → “Run Task” → “▶️ Run Both (8080 + 8081)”
2. VS Code abrirá 2 terminales internas, una para cada servidor.

---

## 6. Microservicio `openai-service` (detalle técnico)

### 6.1. Namespace y autoload
En `openai-service/composer.json`:

```json
"autoload": {
  "psr-4": {
    "Creawebes\\OpenAI\\": "src/"
  }
}
```

Cada vez que se cree o mueva una clase en `src/` hay que ejecutar:

```bash
cd openai-service
composer dump-autoload
```

### 6.2. Punto de entrada
`openai-service/public/index.php`

- Incluye el `vendor/autoload.php`
- Carga manualmente el `.env` usando `putenv()`
- Llama al router del microservicio
- Devuelve SIEMPRE JSON

### 6.3. Controlador principal
Ubicado en `openai-service/src/Controller/OpenAIController.php` (o similar, según tu último cambio).  
Su responsabilidad:
- leer el body JSON de la petición
- validar que vengan `messages`
- delegar en el **servicio** `OpenAIChatService`
- encapsular la respuesta en `{ "ok": true|false, ... }`

### 6.4. Servicio de OpenAI
`openai-service/src/Service/OpenAIChatService.php`

Responsabilidades:

1. Leer la API key:
   ```php
   $apiKey = getenv('OPENAI_API_KEY');
   ```

2. Si no existe → devolver mensaje controlado (no fatal):

   ```php
   return '⚠️ No se ha configurado OPENAI_API_KEY en el entorno.';
   ```

3. Construir la llamada real a OpenAI:

   ```php
   $ch = curl_init('https://api.openai.com/v1/chat/completions');
   // headers, body, etc.
   ```

4. Devolver **solo** el `choices[0].message.content`

5. En caso de error de cURL o HTTP → devolver mensaje controlado

### 6.5. Respuesta estándar del microservicio

- **Éxito:**
  ```json
  {
    "ok": true,
    "content": "historia generada por OpenAI..."
  }
  ```

- **Error controlado (sin matar la app):**
  ```json
  {
    "ok": false,
    "error": "⚠️ No se ha configurado OPENAI_API_KEY en el entorno."
  }
  ```

Esto es importante porque el **frontend ya está preparado** para mostrar un mensaje de “No se pudo generar el cómic” cuando `ok=false`.

---

## 7. Flujo completo app → microservicio → OpenAI

1. Usuario hace clic en **“Generar cómic”** en la UI.
2. Frontend hace `POST /comics/generate` a la app principal (8080).
3. El backend de la app principal hace una petición HTTP al microservicio:
   ```text
   POST http://localhost:8081/v1/chat
   ```
4. El microservicio llama a OpenAI (usando la API key del `.env`).
5. OpenAI responde con una historia en texto.
6. El microservicio devuelve `{ ok: true, content: "..." }` a la app principal.
7. La app principal devuelve esa historia al frontend.
8. El frontend la pinta como “Historia generada”.

Si en cualquier punto hay un error (8081 apagado, API key faltante, OpenAI caído), la app muestra un mensaje bonito en vez de mostrar HTML roto.

---

## 8. Comandos útiles

### 8.1. Probar el microservicio directamente (sin la app)
```bash
curl -X POST http://localhost:8081/v1/chat   -H "Content-Type: application/json"   -d '{
    "messages": [
      { "role": "system", "content": "Eres un narrador de cómics de Marvel en español." },
      { "role": "user", "content": "Crea una escena épica entre Iron Man y Capitán América." }
    ]
  }'
```

Si todo está bien, deberías ver algo tipo:

```json
{"ok":true,"content":"**Título: La Última Frontera** ... "}
```

### 8.2. Ejecutar tests
```bash
vendor/bin/phpunit --colors=always --testdox
```

### 8.3. Análisis estático
```bash
vendor/bin/phpstan analyse --memory-limit=512M
```

### 8.4. Validar composer
```bash
composer validate
```

---

## 9. Errores comunes y cómo resolverlos

### 9.1. “La IA devolvió una estructura inesperada”
- El microservicio no está levantado en 8081
- El microservicio devolvió HTML (por un warning) y no JSON
- Solución: mirar el terminal donde corrés `php -S localhost:8081 -t public` y corregir el error

### 9.2. “⚠️ No se ha configurado OPENAI_API_KEY en el entorno.”
- Existe el `.env` pero no se está cargando
- Revisar que el código de `public/index.php` del microservicio tenga el bloque de `putenv()`
- Revisar que el `.env` esté en la ruta correcta: `openai-service/.env`

### 9.3. 502 Bad Gateway en el navegador
- La app principal intentó hablar con `http://localhost:8081/v1/chat` y no había nada escuchando
- Solución: levantar el microservicio

### 9.4. “Class ... not found”
- Se movió el controlador del microservicio de `src/Http/Controller` a `src/Controller` y no se ejecutó:
  ```bash
  composer dump-autoload
  ```

---

## 10. QA y Git (automatizado)

El proyecto incluye una tarea de VS Code:

```json
{
  "label": "⬆️ Git: add + commit + push (actualiza ambos README)",
  "type": "shell",
  "command": "bash",
  "args": [
    "-c",
    "cp -f clean-marvel/README.md README.md; git add -A; git commit -m \"update clean-marvel + sync README root\" || true; git push"
  ],
  "options": {
    "cwd": "${workspaceFolder}/.."
  }
}
```

Esto hace lo siguiente:
1. Copia el README de la carpeta del proyecto al README raíz
2. Hace `git add -A`
3. Hace commit con mensaje estándar
4. Hace push

Sirve para mantener el README **del proyecto** y el README **del repo raíz** sincronizados.

---

## 11. Seguridad

- No subir **`.env`**
- No subir **keys** en `tasks.json`
- No dejar `var_dump()` o `echo` en los controladores del microservicio porque rompen el JSON
- Mantener `composer.lock` para que todos tengan las mismas versiones

---

## 12. Próximos pasos (roadmap técnico)

- Reemplazar el almacenamiento JSON por **SQLite** o **MySQL** mediante repositorios
- Extraer el microservicio OpenAI a su propio repo
- Añadir autenticación básica a las rutas de administración
- Añadir tests específicos para el microservicio (mock de cURL / OpenAI)
- Dockerizar los dos servicios (8080 y 8081)

---

**Documento generado para el proyecto Creawebes — Clean Marvel Album (actualizado, microservicio funcional).**
