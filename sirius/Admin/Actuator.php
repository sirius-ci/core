<?php

namespace Sirius\Admin;

use Closure;

class Actuator
{
    private $events = array();
    private $defaultEvents = array();

    private $arguments = array();


    public function callEvent($event, array $arguments = [])
    {
        $callable = null;

        if (isset($this->events[$event])) {
            $callable = $this->events[$event];
        } elseif (! empty($this->defaultEvents[$event])) {
            $callable = $this->defaultEvents[$event];
        } else {
            return false;
        }

        return $this->call($callable, $arguments);
    }


    public function call($callable, array $arguments = [])
    {
        // Method tanımlaması yoksa false döndürülür.
        if (empty($callable)) {
            return false;
        }

        // Method Closure ise çalıştırılır.
        if ($callable instanceof Closure) {
            return call_user_func_array($callable, $arguments);
        }

        // Method string tanımdıysa CI objesinin methodu olarak tanımlanır.
        if (is_string($callable)) {
            $callable = [get_instance(), $callable];
        }

        // Method array olarak tanımlanmışsa.
        if (is_array($callable)) {
            // Arrayin ilk elemanı string ise arrayin başına CI objesi eklenerek
            // Method CI objesinin metodu olarak tanımlanır.
            if (is_string($callable[0])) {
                array_unshift($callable, get_instance());
            }

            // Method arrayi 2 elemandan fazla ise argumanlar ayıklanır.
            if (count($callable) > 2) {
                // Tanımlı argumanlar ile Method'dan gönderilen argumanlar birleştirilir.
                // Öncelik Methoddan gönderilen argumanlara verilir.
                // Aynı method'un hem insert'te hem update'te kullanılma durumuna karşın,
                // ön tanımlı argumanlar method'da olmayabileceğinden dolayı hataya sebebiyet verebilir.
                $arguments = array_merge(array_slice($callable, 2), $arguments);

                // Method ayıklanır.
                $callable = array_slice($callable, 0, 2);
            }

            // Method çalıştırılır.
            if (is_callable($callable)) {
                return call_user_func_array($callable, $arguments);
            }
        }

        throw new \Exception('Event calistirilamadi.');
    }


    public function setEvents(array $events)
    {
        $this->events = array_merge($this->events, $events);
    }

    public function setDefaultEvents(array $events)
    {
        $this->defaultEvents = array_merge($this->defaultEvents, $events);
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = array_merge($this->arguments, $arguments);
    }


}