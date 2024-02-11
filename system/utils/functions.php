<?php

/**
 * Affiche le contenu des variables mises en paramètres
 * de manière lisible à des fins de débogage.
 *
 * @param  mixed ...$vars Les variables à afficher.
 * @return void
 */
function display_var(...$vars)
{
    if (empty($vars))
        throw new InvalidArgumentException('La fonction display_var attends au moins un paramètre');

    foreach ($vars as $var)
        if ($var === null)
            throw new InvalidArgumentException('Impossible d\'afficher le contenu d\'une variable "null"');

    echo '<pre>';
    var_dump(...$vars);
    echo '</pre>';
}

/**
 * Vérifie si une chaîne commence par une sous-chaîne donnée.
 *
 * @param  string $haystack La chaîne à vérifier.
 * @param  string $needle   La sous-chaîne à rechercher.
 * @return bool             True si la chaîne commence par la sous-chaîne, false sinon.
 */
function string_starts_with($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

/**
 * Compare deux chaînes de caractères sans tenir compte de la casse.
 *
 * @param  string $string1 La première chaîne à comparer.
 * @param  string $string2 La deuxième chaîne à comparer.
 * @return int             0 si les 2 chaînes sont identiques,
 *                         un entier négatif si la première chaîne est considérée comme inférieure à la deuxième,
 *                         un entier positif si la première chaîne est considérée comme supérieure à la deuxième.
 */
function compare_string_ignore_case($string1, $string2) {
    return strcmp(strtolower($string1), strtolower($string2));
}

/**
 * Récupère la profondeur d'un tableau donné.
 *
 * @param  array $array Le tableau à vérifier.
 * @return int          La profondeur du tableau.
 */
function get_array_depth($array) {
    if (!is_array($array)) return 0;

    $max_depth = 1;
    foreach ($array as $value)
        if (is_array($value)) {
            $depth     = get_array_depth($value) + 1;
            $max_depth = max($max_depth, $depth);
        }

    return $max_depth;
}

/**
 * Vérifie si une chaîne de caractères contient une sous-chaîne.
 *
 * @param  string $haystack La chaîne principale.
 * @param  string $needle   La sous-chaîne à rechercher.
 * @return bool             True si la sous-chaîne est trouvée, false sinon.
 */
function string_contains($haystack, $needle)
{
    return strpos($haystack, $needle) !== false;
}
