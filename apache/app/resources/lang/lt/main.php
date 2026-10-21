<?php

// -----------------------------------------------------------
//  [*] lt/main — the site's own strings, in Lithuanian
//
//  Every text of the member side and its mails in Lithuanian,
//  by key, the same keys as en/main.php. Lithuanian is also
//  the fallback_locale (config/app.php): a key another
//  language's main.php lacks shows in Lithuanian, and a key
//  no file has shows as the raw key. A few keys appear twice;
//  PHP keeps the later entry.
//
//  Known bug (low): no_questions is defined twice
//  (main.php:112, 163) and PHP keeps the later, generic text,
//  so the self-evaluation page, which the first was written
//  for, never says the category has no questions. Fix: give
//  the category text its own key and use that key in
//  self-eval.blade.php.
//  Pinned as lang-no-questions.
//
//  Used by:
//    - the __('main.…') lookups listed in en/main.php, when
//      the site is in Lithuanian
//    - Laravel's translator, for a key the file of the site's
//      language lacks
// -----------------------------------------------------------


return [

    'my_info' => 'Mano informacija',
    'my_badges' => 'Mano Bedžai',
    'mark_completed' => 'Pažymėti kaip atliktą!',
    'completed' => 'Atlikta!',
    'additional_files' => 'Papildomi dokumentai',
    'news' => 'Naujienos',
    'read' => 'Skaityti',
    'no_news' => 'Nėra naujienų',
    'about_cyberphish' => 'Apie CyberPhish',
    'more_about_us' => 'Plačiau apie mus',
    'project_news' => 'Projekto naujienos',
    'more_news' => 'Daugiau naujienų',
    'partners' => 'Partneriai',
    'login' => 'Prisijungti',
    'register' => 'Registruotis',
    'logout' => 'Atsijungti',
    'learning_material' => 'Mokymosi Medžiaga',
    'course_progress' => 'Kurso progresas',
    'curr_password' => 'Dabartinis slaptažodis',
    'new_password' => 'Naujas slaptažodis',
    'repeat_password' => 'Pakartokite naują slaptažodį',
    'change_password' => 'Pakeisti slaptažodį',
    'change_username' => 'Pakeisti vartotojo vardą',
    'change_username2' => 'Pakeisti Vartotojo Vardą',
    'home'=>'Pradžia',
    'contact'=>'Kontaktai',
    'sign_in'=>'Prisijungti',
    'sign_up'=>'Registruotis',
    'language'=>'Kalba',
    'welcome_back'=>'Sveiki sugrįžę!',
    'enter_email'=>'Įveskite el. pašto adresą...',
    'passw'=>'Slaptažodis',
    'repassw'=>'Pakartokite slaptažodį',
    'forgot_password'=>'Pamiršote slaptažodį?',
    'create_acc'=>'Sukurkite paskyrą!',
    'agree_terms'=>'Aš sutinku su naudojimo sąlygomis',
    'already_acc_login'=>'Jau turite paskyrą? Prisijungti!',
    'register'=>'Registruotis',
    'forgot_password_descr'=>'Įveskite savo el. pašto adresą ir atsiųsime slaptažodžio atkūrimo nuorodą!',
    'verify_email'=>'Patvirtinti el. pašto adresą',
    'link_sent'=>'Nuoroda išiųsta el. paštu',
    'link_sent2'=>'Prieš tęsiant įveskite el. paštą patvirtinimo nuorodai gauti',
    'link_sent3'=>'Jei negavote el. laiško',
    'request_another_link'=>'spauskite čia naujai nuorodai',
    'reset_pass'=>'Pakeisti slaptažodį',
    'login_url'=>'Prisijungimo adresas',
    'your_new_pass'=>'Jūsų naujas slaptažodis',
    'link_new_login_info'=>'Norint gauti naujus prisijungimo duomenis, paspauskite žemiau esančią nuorodą.',
    'verify_acc'=>'Patvirtinkite savo paskyrą',
    'new_pass_email_subject'=>'Jūsų naujas slaptažodis',
    'pass_reset_subject'=>'Slaptažodžio keitimo nuoroda',
    'account_verification_subject'=>'Paskyros patvirtinimas',
    'curr_pass_wrong'=>'Dabartinis slaptažodis yra neteisingas',
    'curr_new_same'=>'Dabartinis ir naujas slaptažodis sutampa',
    'new_pass_not_match'=>'Naujas slaptažodis nesutampa su pakartotu',
    'pass_too_short'=>'Slaptažodis per trumpas',
    'pass_changed'=>'Slaptažodis pakeistas!',
    // Despite their keys, these two answer the user name
    // change (ManagerMainController::change_username)
    'pass_updated'=>'Vartotojo vardas pakeistas!',
    'pass_unavailable'=>'Vartotojo vardas neleidžiamas!',
    'auth_failed'=>'Klaidingas vartotojo vardas ir/ar slaptažodis!',
    'forgot_pass_msg'=>'Jei vartotojas su tokiu el. paštu egzistuoja, jam išsiųsime slaptažodžio atkūrimo nuorodą',
    'new_pass_emailed'=>'Kątik nusiuntėme jums naują slaptažodį',
    'user_verified'=>'Vartotojas patvirtintas. Dabar galite prisijungti',
    'user_registered'=>'Vartotojas užregistruotas. El. paštu nusiųsta paskyros patvirtinimo nuoroda',
    'pass_dont_match'=>'Slaptažodžiai nesutampa!',
    'email_already_exists'=>'Vartotojas su šiuo el. paštu jau egzistuoja!',
    'erasmus'=>'This project has been funded with support from the European Commission. This publication [communication] reflects the views only of the author, and the Commission cannot be held responsible for any use which may be made of the information contained therein.',
    'download_slides'=>'Atsisiųsti skaidres',
    'self_evaluation_test'=>'Savitikros testas',
    'self_eval_history'=>'Savitikros istorija',
    'country'=>'Šalis',
    'do_it_again'=>'Atlikti iš naujo',
    'correct_answer'=>'Teisingas atsakymas',
    'wrong_answer'=>'Neteisingas atsakymas',
    'selected_answer'=>'Pasirinktas atsakymas',
    'started'=>'Pradėta',
    'ended'=>'Užbaigta',
    'points'=>'Taškai',
    'next'=>'Kitas',
    'no_questions'=>'Šioje kategorijoje nėra savitikros klausimų!',
    'self_eval_ranks'=>'Savitikros reitingas',
    'nobody'=>'Niekas',
    'username'=>'Vartotojo vardas',
    'ranks'=>'Reitingai',
    'self_eval'=>'Savitikra',
    'simulations'=>'Simuliacijos',
    'simulation'=>'Simuliacija',
    'actors'=>'Aktoriai',
    'choose_type'=>'Tipas',
    'attack_type'=>'Atakos tipas',
    'goal'=>'Tikslas',
    'source'=>'Šaltinis',
    'simulation_categories'=>'Kategorijos',
    'simulation_attributes'=>'Atributai',
    'start'=>'Pradėti',
    'simulation_finished'=>'Simuliacija baigta!',
    'do_simulation_again'=>'Atlikite šią simuliaciją dar kartą',
    'other_simulations'=>'Kitos simuliacijos',
    'points_collected'=>'Surinkti taškai',
    'simulations_history'=>'Simuliacijų istorija',
    'at_least_one'=>'Pasirinkite bent vieną parinktį',
    'simulations_ranks'=>'Simuliacijos reitingai',
    'position'=>'Vieta',
    'last_ended'=>'Paskutinis baigėsi',
    // The two modes of a simulation's start page, keyed in
    // Lithuanian: po kiekvieno, feedback after each step, and
    // pabaigoje, feedback at the end
    'simulation_po_kiekvieno'=>'Mokymosi tikslais',
    'simulation_pabaigoje'=>'Žinių tikrinimo tikslais',
    // my_badges again: PHP keeps this entry
    'my_badges'=>'Mano bedžai',
    // HTML: the success alert prints it unescaped
    // (parts/notif.blade.php), so the link works
    'new_badge'=>"Gavote naują bedžą. Patikrinkite <a href=\"/cp/badges\">Mano bedžus</a>",
    'badge_topic'=>'Užbaikite pirmą temą',
    'badge_self_evaluation_test'=>'Užbaikite pirmajį savitikros testą',
    'badge_category'=>'Užbaikite pirmą kategoriją',
    'badge_all_presentations'=>'Užbaikite visas prezentacijas',
    'badge_all_simulations'=>'Užbaikite visas simuliacijas',
    'badge_finished_course'=>'Užbaikite visą kursą',
    'badge_final_test'=>'Užbaikite galutinį testą',
    'badge_login_10'=>'Prisijunkite 10 dienų iš eilės',
    'knowledge_test'=>'Žinių tikrinimo testas',
    'results'=>'Resultatas',
    'test_finished'=>'Testas atliktas!',
    // Nothing shows only_3 at the moment
    'only_3'=>'Testą pabandyti atlikti galite tik 3 kartus',
    'not_anymore'=>'Daugiau testo negalite atlikti',
    // no_questions again: PHP keeps this entry, so the
    // category text further up never shows
    'no_questions'=>'Klausimų dar nėra',
    'total_tries'=>'Bandymų',
    'you_failed'=>'Jums nepavyko!',
    'you_passed'=>'Testas išlaikytas!',
    'download_certificate'=>'Atsisiųsti sertifikatą',
    'change_fullname'=>'Keisti vardą ir pavardę',
    'fullname'=>'Vardas ir pavardė',
    'updated'=>'Atnaujinta!',
    'pq_questionare'=>'Klausimynas po kurso'
];
