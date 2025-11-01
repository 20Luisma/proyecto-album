# 📘 Casos de Uso — Clean Marvel Album

| Caso de Uso | Descripción | Entrada | Salida | Evento |
|--------------|-------------|----------|---------|---------|
| Crear álbum | Crea un nuevo álbum con nombre y portada | `name`, `coverUrl` | JSON con álbum creado | `AlbumCreatedEvent` |
| Listar álbumes | Devuelve todos los álbumes registrados | — | Lista JSON | — |
| Actualizar álbum | Cambia nombre o portada de un álbum | `albumId`, `data` | JSON actualizado | `AlbumUpdatedEvent` |
| Eliminar álbum | Elimina un álbum y sus héroes | `albumId` | Mensaje de éxito | `AlbumDeletedEvent` |
| Crear héroe | Agrega héroe a un álbum | `albumId`, `name`, `imageUrl` | JSON con héroe | `HeroCreatedEvent` |
| Eliminar héroe | Elimina héroe específico | `heroId` | Mensaje de éxito | `HeroDeletedEvent` |
