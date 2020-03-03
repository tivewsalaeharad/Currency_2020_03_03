<?php
  require 'component.php';

  if(isset($_POST['show_rate'])) {
    $result_rate = show_rate($_POST['curr_abbr']);
  } elseif (isset($_POST['clear_cache'])) {
    $_SESSION['rate_cache'] = [];
    $result_rate = 'Кэш пуст';
  } else {
    $result_rate = 'Валюта не выбрана';
  }

?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Курсы валют</title>
  </head>
  <body>
    <form action="" method="post">
      <input type="text" name="curr_abbr" value="">
      <button type="submit" name="show_rate">Показать курс</button>
      <button type="submit" name="clear_cache">Очистить кэш</button>
    </form>
    <p><?=$result_rate?></p>
  </body>
</html>
