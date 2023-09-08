<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Utils\DateTime;




//Presenter
final class HomePresenter extends Nette\Application\UI\Presenter
{

    //inicializace 
public $url;
public $curl;
public $error;
public $jsonData;
public $filteredData;
public $usedInitials;
public $validItems;


    public function __construct()
    {

        //potřebujeme data a tady si je obstaráme, pokaždé je budeme potřebovat tak nemá smysl je tahat jinak než v contrucotoru třídy
        $this->url = 'https://www.digilabs.cz/hiring/data.php';
        $this->curl = curl_init($this->url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($this->curl);
        if ($response === false) {
            $this->error = 'Chyba při odesílání požadavku: ' . curl_error($this->curl);
        } 
        else 
        {
            $this->jsonData = json_decode($response, true); 
            //bdump($this->jsonData);
        }

    }


    public function renderDefault(): void
    {
        //to co je vidět na první dobrou 
        $this->template->table = $this->jsonData;
       // bdump($this->template->table);
        $this->flashMessage($this->error);
        $this->template->title = "Všechny hodnoty";
        
    }

    public function renderApp1(): void
    {
        $this->setView('meme');
        //meme generator
        $filteredJokes = [];

foreach ($this->jsonData as $item) {
    if (strlen($item['joke']) < 120) {
        $filteredJokes[] = $item['joke'];
    }
}

// Náhodný výběr vtipu z filtrovaných vtipů
$randomJoke = $filteredJokes[array_rand($filteredJokes)];

// Rozdělte vtip na poloviny s respektováním slov
$length = strlen($randomJoke);
$halfLength = round($length / 2);
$halfLength = intval($halfLength);
$this->template->meme[0] = $firstHalf = substr($randomJoke, 0, strrpos(substr($randomJoke, 0, $halfLength), ' '));
$this->template->meme[1] = $secondHalf = substr($randomJoke, strlen($firstHalf));

$this->template->title = "Meme s bambulou v klobouku (však ať si mě nande!)";

    }

    public function renderApp2(): void
    {
        $this->template->title = "Počáteční písmenka";
        $this->setView('default');

        $this->filteredData = [];
        $this->usedInitials = []; // Pole pro udržení již použitých iniciál

foreach ($this->jsonData as $item) {
    $names = explode(' ', $item['name']); // Rozdělení jména na jednotlivá jména

    if (count($names) >= 2) {
        $firstLetters = array_map(function ($name) {
            return substr($name, 0, 1);
        }, $names);

        $firstLetter = $firstLetters[0]; // První písmeno prvního jména

        // Kontrola, zda jsou všechna první písmena stejná
        if (count(array_unique($firstLetters)) === 1) {
            // Kontrola, zda již byla tato iniciála použita
            if (!in_array($firstLetter, $this->usedInitials)) {
                $this->usedInitials[] = $firstLetter; // Přidání iniciály do pole použitých iniciál
               $this->template->table[] = $item; // Přidání záznamu do filtrovaných dat
            }
        }
    }
}





    }

    public function renderApp3(): void
    {
        $this->setView('default');
        $this->template->title = "Dělení čísličkama";
       
        foreach($this->jsonData as $item)
        {
        $firstNumber = $item['firstNumber'];
        $secondNumber = $item['secondNumber'];
        $thirdNumber = $item['thirdNumber'];

    // Podmínky pro výpočet a sudost "firstNumber"
    if ($firstNumber % 2 === 0 && $firstNumber / $secondNumber === $thirdNumber) 
            {
             $this->filteredData[] = $item;
             $this->template->table = $this->filteredData;
             }
        }

    }

    
    public function renderApp4(): void
    {
        $this->template->title = "Datumy";
        $this->setView('default');
        $today = new DateTime(); // Aktuální datum
        $intervalStart = clone $today;
        $intervalStart->modify('-1 month'); // Datum začátku intervalu -1 měsíc
        $intervalEnd = clone $today;
        $intervalEnd->modify('+1 month'); // Datum konce intervalu +1 měsíc

        $filteredData = [];

foreach ($this->jsonData as $item) {
    $createdAt = new DateTime($item['createdAt']);

    if ($createdAt >= $intervalStart && $createdAt <= $intervalEnd) {
        $this->filteredData[] = $item;
        $this->template->table = $this->filteredData;
    }
}
        


    }

    public function renderApp5(): void
    {
        $this->template->title = "Bonus....";
        $this->setView('default');

        foreach ($this->jsonData as $item) {
            $calculation = $item["calculation"];

            // Rozdělíme výraz podle '='
            $parts = explode('=', $calculation);

            if (count($parts) == 2) {
                $leftSide = trim($parts[0]);
                $rightSide = trim($parts[1]);

                $leftNumber = null;
                $rightNumber = null;

                // Zjistíme, která část je číslo a která výraz
                if (is_numeric($leftSide)) {  //pokud je číslo na levé straně
                    $leftNumber = intval($leftSide);
                    $rightNumber = intval($this->calculateExpression($rightSide));
                    if ($leftNumber == $rightNumber)
                    {
                        $this->validItems[] = $item;
                    }

                }
                if (is_numeric($rightSide)) {  //pokud je číslo na pravé straně 
                    $rightNumber = intval($rightSide);
                    $leftNumber = intval($this->calculateExpression($leftSide));
                    if ($leftNumber == $rightNumber)
                    {
                        $this->validItems[] = $item;
                    }
                }

            }
        }

        $this->template->table = $this->validItems;
    }
      
    private function calculateExpression($expression)
    {
        // Rozdělíme výraz podle operátorů + nebo -
        $operators = explode('+', $expression);
        if (count($operators) == 1) {
            $operators = explode('-', $expression);
        }

        $result = intval(trim($operators[0]));
        for ($i = 1; $i < count($operators); $i++) {
            $number = intval(trim($operators[$i]));
            if (strpos($expression, '+') !== false) {
                $result += $number;
            } elseif (strpos($expression, '-') !== false) {
                $result -= $number;
            }
        }

        return $result;
    }
 }


