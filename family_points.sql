-- family_points.sql

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `family_points`
--

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','miembro') NOT NULL DEFAULT 'miembro',
  `puntos` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`nombre`, `password`, `rol`, `puntos`) VALUES
('Admin', '$2y$10$ileOkNet.kyus8QQItd5oOzK9Bpx/8/X/hHpjzacKyNWXdVPCnRMi', 'admin', 0),
('Papá', '$2y$10$ileOkNet.kyus8QQItd5oOzK9Bpx/8/X/hHpjzacKyNWXdVPCnRMi', 'miembro', 0),
('Mamá', '$2y$10$ileOkNet.kyus8QQItd5oOzK9Bpx/8/X/hHpjzacKyNWXdVPCnRMi', 'miembro', 0),
('Hijo', '$2y$10$ileOkNet.kyus8QQItd5oOzK9Bpx/8/X/hHpjzacKyNWXdVPCnRMi', 'miembro', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tareas`
--

CREATE TABLE IF NOT EXISTS `tareas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `puntos` int(11) NOT NULL,
  `tipo` enum('diaria','semanal','especial') NOT NULL DEFAULT 'especial',
  `color` varchar(20) DEFAULT '#3B82F6',
  `icono` varchar(50) DEFAULT 'task',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tareas`
--

INSERT INTO `tareas` (`nombre`, `descripcion`, `puntos`, `tipo`, `color`) VALUES
('Tender la cama', 'Hacer la cama inmediatamente al levantarse', 5, 'diaria', '#10B981'),
('Cepillarse los dientes', 'Cepillarse después de cada comida (3 veces)', 5, 'diaria', '#3B82F6'),
('Guardar los zapatos', 'Dejar los zapatos en su lugar al llegar', 5, 'diaria', '#8B5CF6'),
('Recoger plato después de comer', 'Llevar el plato al lavaplatos después de cada comida', 5, 'diaria', '#EC4899'),
('Apagar luces al salir', 'Verificar que todas las luces estén apagadas', 5, 'diaria', '#F59E0B'),
('Bañarse', 'Ducharse y dejar el baño limpio', 8, 'diaria', '#06B6D4'),
('Ordenar ropa sucia', 'Poner la ropa sucia en el cesto', 8, 'diaria', '#84CC16'),
('Lavar las manos antes de comer', 'Lavarse las manos correctamente', 5, 'diaria', '#14B8A6'),
('Ordenar el cuarto', 'Recoger y organizar el cuarto completamente', 15, 'diaria', '#8B5CF6'),
('Lavar los platos', 'Lavar todos los platos después de una comida', 15, 'diaria', '#3B82F6'),
('Tender la ropa', 'Ayudar a tender la ropa lavada', 12, 'diaria', '#06B6D4'),
('Barrer el cuarto', 'Barrer completamente el cuarto propio', 12, 'diaria', '#F59E0B'),
('Organizar escritorio', 'Mantener escritorio/mesa de estudio ordenado', 10, 'diaria', '#EC4899'),
('Sacar la basura', 'Llevar la basura al lugar de recolección', 15, 'diaria', '#EF4444'),
('Dar de comer a las mascotas', 'Alimentar y dar agua a las mascotas (si hay)', 12, 'diaria', '#F59E0B'),
('Ayudar a preparar la mesa', 'Poner los cubiertos y preparar la mesa', 10, 'diaria', '#8B5CF6'),
('Recoger la mesa', 'Quitar todos los platos y limpiar la mesa', 12, 'diaria', '#10B981'),
('Limpiar el baño', 'Limpieza completa: inodoro, lavamanos, ducha', 40, 'semanal', '#06B6D4'),
('Trapear la cocina', 'Trapear el piso de la cocina completamente', 35, 'semanal', '#10B981'),
('Limpiar ventanas del cuarto', 'Limpiar ventanas por dentro y por fuera', 30, 'semanal', '#3B82F6'),
('Organizar el closet', 'Doblar y organizar toda la ropa', 35, 'semanal', '#8B5CF6'),
('Limpiar los espejos', 'Limpiar todos los espejos de la casa', 25, 'semanal', '#EC4899'),
('Sacudir los muebles', 'Quitar el polvo de todos los muebles', 30, 'semanal', '#F59E0B'),
('Limpiar la nevera', 'Limpiar y organizar el refrigerador', 40, 'semanal', '#14B8A6'),
('Barrer el patio/garaje', 'Barrer áreas externas completamente', 35, 'semanal', '#84CC16'),
('Lavar el carro', 'Lavar, aspirar y limpiar el carro completo', 80, 'especial', '#3B82F6'),
('Limpiar vidrios externos', 'Limpiar todas las ventanas externas de la casa', 70, 'especial', '#06B6D4'),
('Organizar la despensa', 'Revisar fechas, organizar y limpiar la despensa', 60, 'especial', '#F59E0B'),
('Limpiar el garaje', 'Organizar y barrer el garaje completo', 100, 'especial', '#8B5CF6'),
('Lavar las cortinas', 'Quitar, lavar y colgar las cortinas', 80, 'especial', '#EC4899'),
('Limpieza profunda del cuarto', 'Mover muebles, limpiar detrás, debajo, todo', 120, 'especial', '#8B5CF6'),
('Limpiar el horno/estufa', 'Limpieza profunda del horno y quemadores', 70, 'especial', '#EF4444'),
('Organizar bodega/trastero', 'Organizar área de almacenamiento', 90, 'especial', '#84CC16'),
('Terminar todas las tareas escolares', 'Completar todos los deberes del día', 25, 'diaria', '#3B82F6'),
('Estudiar 1 hora', 'Estudiar o repasar lecciones por 1 hora', 30, 'diaria', '#8B5CF6'),
('Leer 30 minutos', 'Leer un libro por 30 minutos', 20, 'diaria', '#EC4899'),
('Practicar instrumento/hobby', 'Practicar instrumento musical o hobby por 30 min', 25, 'diaria', '#F59E0B'),
('Hacer ejercicio', '30 minutos de actividad física', 20, 'diaria', '#EF4444'),
('No usar pantallas por 1 día', 'No usar celular/tablet/videojuegos voluntariamente', 100, 'especial', '#10B981'),
('Sacar buena nota en examen', 'Obtener nota excelente (9 o 10)', 80, 'especial', '#F59E0B'),
('Terminar proyecto escolar', 'Completar proyecto importante antes de tiempo', 120, 'especial', '#8B5CF6'),
('Cuidar hermanos menores', 'Cuidar hermanos por 2+ horas', 50, 'especial', '#EC4899'),
('Hacer mercado con los papás', 'Ayudar a hacer las compras del mercado', 40, 'especial', '#10B981'),
('Cocinar una comida', 'Preparar una comida completa (con supervisión)', 60, 'especial', '#EF4444'),
('Lavar ropa (ciclo completo)', 'Lavar, tender y doblar la ropa', 50, 'especial', '#06B6D4'),
('Planchar ropa', 'Planchar ropa de la familia', 45, 'especial', '#F59E0B');
-- --------------------------------------------------------

--
-- Table structure for table `tareas_completadas`
--

CREATE TABLE IF NOT EXISTS `tareas_completadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tarea_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_reclamada` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','validada','rechazada') NOT NULL DEFAULT 'pendiente',
  `fecha_validada` timestamp NULL DEFAULT NULL,
  `validada_por` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tarea_id` (`tarea_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `validada_por` (`validada_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `premios`
--

CREATE TABLE IF NOT EXISTS `premios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `costo_puntos` int(11) NOT NULL,
  `tipo` enum('robux','tiempo','especial') NOT NULL DEFAULT 'especial',
  `cantidad` varchar(50) DEFAULT NULL,
  `icono` varchar(50) DEFAULT 'gift',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `premios`
--

INSERT INTO `premios` (`nombre`, `descripcion`, `costo_puntos`, `tipo`, `cantidad`) VALUES
('10 min extra de pantalla', '10 minutos adicionales de tiempo de pantalla', 50, 'tiempo', '10'),
('20 min extra de pantalla', '20 minutos adicionales de tiempo de pantalla', 90, 'tiempo', '20'),
('Elegir el postre', 'Elegir el postre del día', 80, 'especial', '1'),
('Elegir película familiar', 'Elegir qué película ver en familia', 100, 'especial', '1'),
('Quedarse despierto 30 min extra', 'Extender hora de dormir 30 minutos', 120, 'tiempo', '30'),
('40 Robux', 'Recarga de 40 Robux en Roblox', 150, 'robux', '40'),
('30 min extra de pantalla', '30 minutos adicionales de tiempo de pantalla', 150, 'tiempo', '30'),
('1 hora extra de pantalla', '1 hora adicional de tiempo de pantalla', 250, 'tiempo', '60'),
('Salida al parque', 'Salida especial al parque o lugar favorito', 200, 'especial', '1'),
('Elegir almuerzo del domingo', 'Elegir qué comer el domingo', 180, 'especial', '1'),
('Invitar un amigo a casa', 'Invitar a un amigo a pasar el día', 220, 'especial', '1'),
('Día sin oficio', 'Un día libre de todas las tareas del hogar', 280, 'especial', '1'),
('80 Robux', 'Recarga de 80 Robux en Roblox', 300, 'robux', '80'),
('Salida al cine', 'Ir al cine con la familia', 400, 'especial', '1'),
('Día de pizza', 'Ordenar pizza para toda la familia', 350, 'especial', '1'),
('Helado para todos', 'Ir por helados en familia', 320, 'especial', '1'),
('Comida rápida favorita', 'Ordenar tu comida rápida favorita', 300, 'especial', '1'),
('Juguete pequeño', 'Comprar un juguete o artículo hasta $20,000', 400, 'especial', '1'),
('400 Robux', 'Recarga de 400 Robux en Roblox', 600, 'robux', '400'),
('Salida especial en familia', 'Parque de diversiones, museo, etc.', 800, 'especial', '1'),
('Videojuego nuevo', 'Comprar un videojuego hasta $50,000', 1000, 'especial', '1'),
('Juguete grande', 'Juguete o artículo especial hasta $50,000', 1000, 'especial', '1'),
('Ropa nueva', 'Elegir ropa nueva hasta $50,000', 1000, 'especial', '1'),
('Fin de semana especial', 'Fin de semana de actividades especiales', 1500, 'especial', '1');

-- --------------------------------------------------------

--
-- Table structure for table `canjes`
--

CREATE TABLE IF NOT EXISTS `canjes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `premio_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_canje` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','entregado') NOT NULL DEFAULT 'pendiente',
  `puntos_gastados` int(11) NOT NULL,
  `fecha_entrega` timestamp NULL DEFAULT NULL,
  `entregado_por` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `premio_id` (`premio_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `entregado_por` (`entregado_por`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `historial_puntos`
--

CREATE TABLE IF NOT EXISTS `historial_puntos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `puntos_antes` int(11) NOT NULL,
  `puntos_cambio` int(11) NOT NULL,
  `puntos_despues` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `referencia_tipo` varchar(50) DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sesiones`
--

CREATE TABLE IF NOT EXISTS `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expira_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tareas_completadas`
--
ALTER TABLE `tareas_completadas`
  ADD CONSTRAINT `tc_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`),
  ADD CONSTRAINT `tc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `tc_validador` FOREIGN KEY (`validada_por`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `canjes`
--
ALTER TABLE `canjes`
  ADD CONSTRAINT `cnj_premio` FOREIGN KEY (`premio_id`) REFERENCES `premios` (`id`),
  ADD CONSTRAINT `cnj_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `cnj_entregador` FOREIGN KEY (`entregado_por`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `historial_puntos`
--
ALTER TABLE `historial_puntos`
  ADD CONSTRAINT `hp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `sess_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

COMMIT;
