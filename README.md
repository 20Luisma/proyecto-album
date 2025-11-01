# Clean Marvel Album + Microservicio OpenAI

Este proyecto está formado por **una app principal PHP (Clean Marvel Album)** y **un microservicio separado** que se encarga de hablar con la API de OpenAI para generar las historias de los cómics.

El objetivo: desde la app principal seleccionas héroes → pulsas **“Generar cómic”** → la app llama al microservicio → el microservicio llama a OpenAI → devuelve la historia → la app la muestra.

---

## Estructura del proyecto

```
.
├── clean-marvel/               # App principal (frontend + backend PHP)
│   ├── public/                 # index.php → se sirve en http://localhost:8080
│   └── src/
│
└── clean-marvel/openai-service/ # Microservicio de IA (PHP)
    ├── public/                 # index.php → se sirve en http://localhost:8081
    └── src/
```

---

## Requisitos

- PHP 8.2+
- Composer
- Cuenta de OpenAI y **API key**
- (Opcional) VS Code con `.vscode/tasks.json` para levantar los dos servers

---

## Instalación

1. Clonar el repo
2. Instalar dependencias del proyecto principal:

   ```bash
   cd clean-marvel
   composer install
   ```

3. Instalar dependencias del microservicio:

   ```bash
   cd openai-service
   composer install
   ```

---

## Configuración del microservicio (`openai-service/`)

El microservicio necesita una **API key** de OpenAI.

1. Crear el archivo:

   ```bash
   cd clean-marvel/openai-service
   cp .env.example .env   # si lo tienes, si no créalo
   ```

2. Dentro de `.env` poner:

   ```env
   OPENAI_API_KEY=sk-tu-clave-real
   ```

3. El archivo `.env` **NO se sube** a GitHub. Ya está añadido en `.gitignore`.

4. El archivo `public/index.php` ya está preparado para **cargar el `.env` manualmente** (lee línea por línea y hace `putenv()`), por lo que no hace falta exportar la variable cada vez.

---

## Cómo levantar los dos servidores

### 1. App principal (8080)

Desde `clean-marvel/`:

```bash
php -S localhost:8080 -t public
```

Esto abre la app donde están los héroes y el botón **“Generar cómic”**.

---

### 2. Microservicio OpenAI (8081)

En otra terminal:

```bash
cd clean-marvel/openai-service
php -S localhost:8081 -t public
```

⚠️ Importante: el microservicio debe estar levantado **antes** de darle a “Generar cómic”, porque la app principal le hace un `curl` a:

```
http://localhost:8081/v1/chat
```

Si el 8081 no está levantado, la app principal devuelve `502 Bad Gateway` y el frontend muestra “La IA devolvió una estructura inesperada”.

---

## Cómo funciona el flujo

1. El usuario hace clic en **“Generar cómic”** en la app (8080).
2. La app (8080) hace un `POST /comics/generate` a su propio backend.
3. El backend de la app hace una llamada HTTP al **microservicio**:

   ```bash
   POST http://localhost:8081/v1/chat
   Content-Type: application/json

   {
     "messages": [
       { "role": "system", "content": "Eres un narrador de cómics de Marvel en español." },
       { "role": "user", "content": "Crea una escena épica entre Iron Man y Capitán América." }
     ]
   }
   ```

4. El microservicio recibe eso, llama a:

   ```text
   https://api.openai.com/v1/chat/completions
   ```

   usando la `OPENAI_API_KEY` del `.env`.

5. OpenAI devuelve la historia.
6. El microservicio responde SIEMPRE JSON al proyecto principal:

   ```json
   {
     "ok": true,
     "content": "Iron Man sobrevolaba Nueva York..."
   }
   ```

7. La app principal pinta el cómic.

---

## Endpoints del microservicio

- **POST** `/v1/chat` → endpoint principal
  - Body:
    ```json
    {
      "messages": [
        { "role": "system", "content": "..." },
        { "role": "user", "content": "..." }
      ]
    }
    ```
  - Respuesta OK:
    ```json
    {
      "ok": true,
      "content": "historia generada..."
    }
    ```
  - Respuesta error controlado:
    ```json
    {
      "ok": false,
      "error": "⚠️ OpenAI no respondió..."
    }
    ```

- **GET** `/health` → opcional, para saber si el microservicio está vivo
  ```json
  { "ok": true, "service": "openai-service" }
  ```

---

## Dependencias principales

### App principal (`clean-marvel/`)

- PHP 8.2
- composer.json (PSR-4, router propio)
- PHPUnit (tests)
- PHPStan (análisis)

### Microservicio (`openai-service/`)

- PHP 8.2
- cURL habilitado (para llamar a OpenAI)
- `.env` local (no se sube)
- Autoload PSR-4:
  ```json
  "autoload": {
    "psr-4": {
      "Creawebes\\OpenAI\\": "src/"
    }
  }
  ```

Después de crear / mover clases:

```bash
composer dump-autoload
```

---

## VS Code: tasks

En `.vscode/tasks.json` se pueden definir 2 tasks:

- **“🚀 Iniciar servidor PHP (8080)”** → sirve `clean-marvel/public`
- **“🤖 Run OpenAI Service (8081)”** → sirve `clean-marvel/openai-service/public`
- **“▶️ Run Both (8080 + 8081)”** → los dos en paralelo

*(si este archivo se sube a GitHub, NO pongas la API key dentro del task)*

---

## Notas de seguridad

- **No subas** el archivo `.env`
- **No pongas** la API key dentro del código PHP
- **No pongas** la API key en `tasks.json` si el repo es público
- Si alguien clona el proyecto, debe crear su propio `.env` en `openai-service/`

---

## Problemas comunes

- **502 Bad Gateway al generar cómic** → el microservicio (8081) no está levantado.
- **“⚠️ No se ha configurado OPENAI_API_KEY…”** → el `.env` existe pero no se está cargando → revisar `public/index.php` del microservicio.
- **“La IA devolvió una estructura inesperada”** → el microservicio devolvió HTML o un error en vez de JSON → revisar que no haya `echo` de debug.
- **“Class ... not found”** → falta `composer dump-autoload` o el namespace no coincide.

✍️ Autor

Proyecto desarrollado por **Martín Pallante**  
con la colaboración de **Alfred**, asistente de IA.  

[🌐 Creawebes](https://www.creawebes.com) · © 2025  

> “Diseñando tecnología limpia, modular y con propósito.”
