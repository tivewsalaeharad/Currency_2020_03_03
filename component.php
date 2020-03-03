<?php

$myAPIkey = 'c6d73545d441fd7877d107c0cbb26f4f';

/**
 * Поиск курса в базе данных
 */
class RateFromDB {
  private $link;
  private $error;

  public function init() {
      $this->link = mysqli_connect('localhost', 'root', '', 'admin_default');
      if (mysqli_connect_errno()) {
        $this->error = mysqli_connect_error();
      } else {
        $this->error = null;
        $this->createTableIfNotExists();
      }
  }

  public function fetch_rate($curr_abbr) {
    if ($this->error) return null;
    $day = date('Y-m-d', time());
    $query = "SELECT rate FROM currency WHERE name = '$curr_abbr' AND day = '$day'";

    if ($stmt = mysqli_prepare($this->link, $query)) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $rate);
        if (mysqli_stmt_fetch($stmt)) {
            $result = $rate;
        } else {
            $result = null;
        }
        mysqli_stmt_close($stmt);
        return $result;
      } else {
        return null;
      }
  }

  public function write_rate($curr_abbr, $rate) {
    $day = date('Y-m-d', time());
    $query = "INSERT INTO currency (name, rate, day) VALUES ('$curr_abbr', $rate, '$day')";
    if ($stmt = mysqli_prepare($this->link, $query)) {
      mysqli_stmt_execute($stmt);
    }
  }

  public function close() {
      mysqli_close($this->link);
  }

  private function createTableIfNotExists() {
    $query = "CREATE TABLE IF NOT EXISTS currency (
        id INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
        name VARCHAR(3) NOT NULL,
        rate DECIMAL(10, 4) NOT NULL,
        day VARCHAR(10) NOT NULL
    )";
    if ($stmt = mysqli_prepare($this->link, $query)) {
      mysqli_stmt_execute($stmt);
    }
  }

}

/**
 * Запрос на сервер
 */
class RateFromServer {
  private $base_url = "https://currate.ru/api/";
  private $token;
  private $handle;

  function __construct($token) {
      $this->token = $token;
  }

  public function init() {
      $this->handle = curl_init();
      curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
  }

  public function fetch_rate($curr_abbr) {
    $direction = "{$curr_abbr}RUB";
    $url = "{$this->base_url}?get=rates&pairs={$direction}&key={$this->token}";
    $response_array = $this->executeRequest($url);
    if ($response_array['status'] == "200") {
      return $response_array['data'][$direction];
    } else {
      return null;
    }
  }

  public function close() {
      curl_close($this->handle);
  }

  private function executeRequest($url) {
      curl_setopt($this->handle, CURLOPT_URL, $url);
      $result = curl_exec($this->handle);
      $result = json_decode($result, true);
      return $result;
  }

}
/*
* Найти курс валют по схеме: кэш - база данных - сервер
* @param string $curr_abbr Аббревиатура валюты
*/
function show_rate($curr_abbr) {
  if(!isset($_SESSION['rate_cache'])) {
    $_SESSION['rate_cache'] = [];
  }
  $rate = $_SESSION['rate_cache'][$curr_abbr];
  if ($rate) {
    $result = "$curr_abbr/RUB = $rate (Обращение к кэшу)";
  } else {
    $curr_rate_db = new RateFromDB();
    $curr_rate_db->init();
    $rate = $curr_rate_db->fetch_rate($curr_abbr);
    if ($rate) {
      $_SESSION['rate_cache'][$curr_abbr] = $rate;
      $result = "$curr_abbr/RUB = $rate (Обращение к базе данных)";
    } else {
      $curr_rate_server = new RateFromServer($myAPIkey);
      $curr_rate_server->init();
      $rate = $curr_rate_server->fetch_rate($curr_abbr);
      if ($rate) {
        $_SESSION['rate_cache'][$curr_abbr] = $rate;
        $curr_rate_db->write_rate($curr_abbr, $rate);
        $result = "$curr_abbr/RUB = $rate (Обращение к серверу)";
      } else {
        $result = "Валюта с аббревиатурой \"$curr_abbr\" не найдена";
      }
      $curr_rate_server->close();
    }
    $curr_rate_db->close();
  }
  return $result;
}

session_start();

?>
