-- migracion_p8.sql · AgendaWeb · práctica P8
-- Local: Workbench. En DomCloud: quita la línea USE y elige tu base en phpMyAdmin.
USE agenda;

-- ═══ PARTE 1 · Respaldo y tabla de usuarios ═══
CREATE TABLE eventos_respaldo_p8 AS SELECT * FROM eventos;

CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- ═══ ENTRE PARTES: regístrate en registro.php y consulta tu id ═══
-- SELECT id, nombre, correo, clave_hash FROM usuarios;

-- ═══ PARTE 2 · Cada evento con dueño (cambia el 1 por TU id) ═══
ALTER TABLE eventos ADD COLUMN usuario_id INT NULL AFTER id;

UPDATE eventos SET usuario_id = 1 WHERE id > 0;

ALTER TABLE eventos
  MODIFY usuario_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_usuario
      FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
      ON DELETE CASCADE;

DESCRIBE eventos;