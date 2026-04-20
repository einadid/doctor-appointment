<?php
session_start();
session_unset(); // সব সেশন ভেরিয়েবল মুছে ফেলা
session_destroy(); // সেশন ধ্বংস করা

header("Location: ../../index.html"); // হোমে ফেরত পাঠানো
exit();
?>