-- ============================================================================
-- Copiar TODAS las técnicas (filas de mybb_sg_sg_tec_aprendidas) de un FID a otro.
--
-- El destino queda con EXACTAMENTE las mismas técnicas que el origen: primero se
-- borra lo que tenía el destino y luego se copian las del origen.
--
-- No se copia `id` (auto_increment) ni `tiempo` (timestamp automático).
--
-- Ajusta @origen y @destino y ejecútalo.
-- ============================================================================

SET @origen  = 300;  -- FID del que se COPIAN las técnicas
SET @destino = 200;  -- FID que las RECIBE (primero se borra lo suyo)

START TRANSACTION;

-- 1) Borra todo lo que tenía el destino
DELETE FROM `mybb_sg_sg_tec_aprendidas` WHERE `uid` = @destino;

-- 2) Copia todas las filas del origen al destino
INSERT INTO `mybb_sg_sg_tec_aprendidas` (`tid`, `uid`)
SELECT `tid`, @destino
FROM `mybb_sg_sg_tec_aprendidas`
WHERE `uid` = @origen;

COMMIT;

-- Verificación (opcional): deben coincidir las cuentas de origen y destino.
--   SELECT uid, COUNT(*) FROM `mybb_sg_sg_tec_aprendidas`
--   WHERE uid IN (@origen, @destino) GROUP BY uid;
