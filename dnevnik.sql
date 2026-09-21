-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Сен 21 2026 г., 12:45
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `dnevnik`
--

-- --------------------------------------------------------

--
-- Структура таблицы `otziv`
--

CREATE TABLE `otziv` (
  `id_otziv` int(1) NOT NULL,
  `coment` varchar(255) DEFAULT NULL,
  `zvezda` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `otziv`
--

INSERT INTO `otziv` (`id_otziv`, `coment`, `zvezda`) VALUES
(1, 'комментарий', 5);

-- --------------------------------------------------------

--
-- Структура таблицы `polzovateli`
--

CREATE TABLE `polzovateli` (
  `id_polzovatelya` int(1) NOT NULL,
  `login` varchar(255) NOT NULL,
  `password` int(255) NOT NULL,
  `fio` varchar(255) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `polzovateli`
--

INSERT INTO `polzovateli` (`id_polzovatelya`, `login`, `password`, `fio`, `phone`, `email`, `role_id`) VALUES
(1, 'Admin', 87654321, 'Лобанова Анастасия Витальевна', '8(925)526-66-01', 'example@gmail.com', 2),
(2, 'User', 12345678, 'Кулькова Ульяна Андреевна', '8(977)279-35-70', 'bebe@mail.ru', 1),
(3, 'Test', 13254768, 'Астахов Денис Сергеевич', '8(999)970-91-60', 'ban@mail.ru', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `roli`
--

CREATE TABLE `roli` (
  `id_roli` int(1) NOT NULL,
  `rol` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `roli`
--

INSERT INTO `roli` (`id_roli`, `rol`) VALUES
(1, 'пользователь'),
(2, 'администратор');

-- --------------------------------------------------------

--
-- Структура таблицы `statusi`
--

CREATE TABLE `statusi` (
  `id_statusi` int(1) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `statusi`
--

INSERT INTO `statusi` (`id_statusi`, `status`) VALUES
(1, 'утверждено'),
(2, 'на доработке'),
(3, 'заполнено');

-- --------------------------------------------------------

--
-- Структура таблицы `zadania`
--

CREATE TABLE `zadania` (
  `id_zadaniya` int(1) NOT NULL,
  `zadanie` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `zadania`
--

INSERT INTO `zadania` (`id_zadaniya`, `zadanie`) VALUES
(1, 'Изучение структуры предприятия'),
(2, 'Выполнение заданий наставника'),
(3, 'Работа с документацией'),
(4, 'Участие в совещаниях'),
(5, 'Самостоятельная работа');

-- --------------------------------------------------------

--
-- Структура таблицы `zayvka`
--

CREATE TABLE `zayvka` (
  `id_zayavki` int(1) NOT NULL,
  `polzovatelya_id` int(1) DEFAULT NULL,
  `data_zapisi` varchar(255) NOT NULL,
  `zadaniya_id` int(1) DEFAULT NULL,
  `kolichestvo_chasov` int(2) DEFAULT NULL,
  `status_id` int(1) DEFAULT NULL,
  `otziv_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `zayvka`
--

INSERT INTO `zayvka` (`id_zayavki`, `polzovatelya_id`, `data_zapisi`, `zadaniya_id`, `kolichestvo_chasov`, `status_id`, `otziv_id`) VALUES
(1, 2, '2026-09-15', 1, 35, 1, 1),
(2, 1, '2026-09-13', 2, 42, 2, 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `otziv`
--
ALTER TABLE `otziv`
  ADD PRIMARY KEY (`id_otziv`);

--
-- Индексы таблицы `polzovateli`
--
ALTER TABLE `polzovateli`
  ADD PRIMARY KEY (`id_polzovatelya`),
  ADD KEY `role_id` (`role_id`);

--
-- Индексы таблицы `roli`
--
ALTER TABLE `roli`
  ADD PRIMARY KEY (`id_roli`);

--
-- Индексы таблицы `statusi`
--
ALTER TABLE `statusi`
  ADD PRIMARY KEY (`id_statusi`);

--
-- Индексы таблицы `zadania`
--
ALTER TABLE `zadania`
  ADD PRIMARY KEY (`id_zadaniya`);

--
-- Индексы таблицы `zayvka`
--
ALTER TABLE `zayvka`
  ADD PRIMARY KEY (`id_zayavki`),
  ADD KEY `polzovatelya_id` (`polzovatelya_id`),
  ADD KEY `polzovatelya_id_2` (`polzovatelya_id`),
  ADD KEY `otziv_id` (`otziv_id`),
  ADD KEY `zadanie_id` (`zadaniya_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `polzovateli`
--
ALTER TABLE `polzovateli`
  ADD CONSTRAINT `polzovateli_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roli` (`id_roli`);

--
-- Ограничения внешнего ключа таблицы `zayvka`
--
ALTER TABLE `zayvka`
  ADD CONSTRAINT `zayvka_ibfk_1` FOREIGN KEY (`polzovatelya_id`) REFERENCES `polzovateli` (`id_polzovatelya`),
  ADD CONSTRAINT `zayvka_ibfk_2` FOREIGN KEY (`zadaniya_id`) REFERENCES `zadania` (`id_zadaniya`),
  ADD CONSTRAINT `zayvka_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `statusi` (`id_statusi`),
  ADD CONSTRAINT `zayvka_ibfk_4` FOREIGN KEY (`otziv_id`) REFERENCES `otziv` (`id_otziv`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
