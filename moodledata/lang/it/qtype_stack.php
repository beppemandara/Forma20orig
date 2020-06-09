<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'qtype_stack', language 'it', branch 'MOODLE_27_STABLE'
 *
 * @package   qtype_stack
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addanothernode'] = 'Aggiungi  un altro nodo';
$string['alg_inequalities_name'] = 'Disuguaglianze';
$string['alg_logarithms_name'] = 'Le leggi dei logaritmi';
$string['allowwords'] = 'Parole consentite';
$string['ATAlgEquiv_SA_not_equation'] = 'La tua risposta dovrebbe essere una equazione, ma non lo è.';
$string['ATAlgEquiv_SA_not_expression'] = 'La tua risposta dovrebbe essere un\'espressione, non un\'equazione, disuguaglianza, elenco, inseme o matrice.';
$string['ATAlgEquiv_SA_not_inequality'] = 'La tua risposta dovrebbe essere una disuguaglianza, ma non lo è.';
$string['ATAlgEquiv_SA_not_matrix'] = 'La tua risposta dovrebbe essere una matrice, ma non lo è.';
$string['ATAlgEquiv_SA_not_set'] = 'La tua risposta dovrebbe essere un insieme, ma non lo è. Per inserire un insieme racchiudi tra parentesi graffe l\'elenco degli elementi separati da virgola.';
$string['ATAlgEquiv_TA_not_equation'] = 'Hai inserito un\'equazione, ma la risposta non è un\'equazione. Forse hai scritto qualcosa come y=2*x+1 mentre avresti dovuto scrivere solo 2*x+1.';
$string['ATCompSquare_not_AlgEquiv'] = 'La tua risposta è in una forma corretta, ma non è equivalente alla risposta corretta.';
$string['ATCompSquare_SA_not_depend_var'] = 'La tua risposta dovrebbe dipendere dalla variabile {$a->m0} ma così non è.';
$string['ATDiff_int'] = 'Sembra invece che tu abbia integrato.';
$string['ATFacForm_error_degreeSA'] = 'Il sistema non ha potuto stabilire il grado della tua risposta.';
$string['ATFacForm_isfactored'] = 'La risposta è fattorizzata, bene.';
$string['ATFacForm_notalgequiv'] = 'La tua risposta non è algebricamente equivalente alla risposta corretta. Devi aver sbagliato qualcosa.';
$string['ATFacForm_notfactored'] = 'La tua risposta non è fattorizzata.';
$string['ATInt_diff'] = 'Sembra che invece tu abbia differenziato.';
$string['ATList_wrongentries'] = 'Ciò che sotto è sottolineato in rosso è  sbagliato. {$a->m0}';
$string['ATList_wronglen'] = 'Il tuo elenco dovrebbe contenere {$a->m0} elementi, mentre ne contiene {$a->m1}.';
$string['ATNumDecPlaces_NoDP'] = 'La tua risposta deve essere un numero in formato decimale, incluso il punto decimale.';
$string['ATNumDecPlaces_Wrong_DPs'] = 'La tua risposta ha un numero errato di cifre decimali.';
$string['ATNumerical_SA_not_set'] = 'La tua risposta dovrebbe essere un insieme. Per digitare un insieme racchiudi tra parentesi graffe i valori separati da virgola.';
$string['ATNumSigFigs_NotDecimal'] = 'La tua risposta dovrebbe essere un numero in formato decimale, ma non lo è.';
$string['ATNumSigFigs_WrongDigits'] = 'La tua risposta contiene un numero di cifre significative errato.';
$string['ATPartFrac_denom_ret'] = 'Se la tua risposta fosse scritta come un\'unica frazione allora il denominatore sarebbe {$a->m0}. Invece, dovrebbe essere {$a->m1}.';
$string['ATPartFrac_diff_variables'] = 'Le variabili nella tua risposta sono differenti da quelle della domanda, controllale.';
$string['ATPartFrac_ret_expression'] = 'La tua risposta come singola frazione è {$a->m0}';
$string['calc_diff_standard_derivatives_fact'] = 'La seguente tabella mostra le derivate di alcune funzioni fondamentali. E\' importante imparare queste derivate perché sono usate spesso.
<center>
<table>
<tr><th>(f(x)) </th><th> (f\'(x))</th></tr>
<tr>
<td>(k), constant </td> <td> (0) </td> </tr> <tr> <td>
(x^n), any constant (n) </td> <td> (nx^{n-1})</td> </tr> <tr> <td>
(e^x) </td> <td> (e^x)</td> </tr> <tr> <td>
(ln(x)=log_{rm e}(x)) </td> <td> (frac{1}{x}) </td> </tr> <tr> <td>
(sin(x)) </td> <td> (cos(x)) </td> </tr> <tr> <td>
(cos(x)) </td> <td> (-sin(x)) </td> </tr> <tr> <td>
(tan(x) = frac{sin(x)}{cos(x)}) </td> <td> (sec^2(x)) </td> </tr> <tr> <td>
(cosec(x)=frac{1}{sin(x)}) </td> <td> (-cosec(x)cot(x)) </td> </tr> <tr> <td>
(sec(x)=frac{1}{cos(x)}) </td> <td> (sec(x)tan(x)) </td> </tr> <tr> <td>
(cot(x)=frac{cos(x)}{sin(x)}) </td> <td> (-cosec^2(x)) </td> </tr> <tr> <td>
(cosh(x)) </td> <td> (sinh(x)) </td> </tr> <tr> <td>
(sinh(x)) </td> <td> (cosh(x)) </td> </tr> <tr> <td>
(tanh(x)) </td> <td> (sech^2(x)) </td> </tr> <tr> <td>
(sech(x)) </td> <td> (-sech(x)tanh(x)) </td> </tr> <tr> <td>
(cosech(x)) </td> <td> (-cosech(x)coth(x)) </td> </tr> <tr> <td>
(coth(x)) </td> <td> (-cosech^2(x)) </td> </tr>
</table>
</center>

[ frac{d}{dx}left(sin^{-1}(x)right) = frac{1}{sqrt{1-x^2}}]
[ frac{d}{dx}left(cos^{-1}(x)right) = frac{-1}{sqrt{1-x^2}}]
[ frac{d}{dx}left(tan^{-1}(x)right) = frac{1}{1+x^2}]
[ frac{d}{dx}left(cosh^{-1}(x)right) = frac{1}{sqrt{x^2-1}}]
[ frac{d}{dx}left(sinh^{-1}(x)right) = frac{1}{sqrt{x^2+1}}]
[ frac{d}{dx}left(tanh^{-1}(x)right) = frac{1}{1-x^2}]';
$string['calc_diff_standard_derivatives_name'] = 'Derivate fondamentali.';
$string['calc_int_methods_substitution_name'] = 'Integrazione per sostituzione';
$string['calc_product_rule_fact'] = 'La seguente regola consente di derivare il prodotto di due funzioni. Supponi di dover derivare (f(x)g(x)) rispetto ad (x).
[ frac{mathrm{d}}{mathrm{d}{x}} big(f(x)g(x)big) = f(x) cdot frac{mathrm{d} g(x)}{mathrm{d}{x}} + g(x)cdot frac{mathrm{d} f(x)}{mathrm{d}{x}},] or, using alternative notation, [ (f(x)g(x))\' = f\'(x)g(x)+f(x)g\'(x). ]';
$string['calc_product_rule_name'] = 'Derivata di un prodotto';
$string['calc_quotient_rule_fact'] = 'La derivata del quoziente di due funzioni derivabili è:';
$string['calc_quotient_rule_name'] = 'Derivata di un quoziente.';
$string['forbidwords'] = 'Parole proibite';
$string['forbidwords_help'] = 'Questo è un elenco di stringhe di testo separate da virgola, ma inserire ciò è vietato in una risposta';
$string['generalfeedback'] = 'Feedback generale';
$string['greek_alphabet_name'] = 'Alfabeto greco';
$string['healthautomaxopt'] = 'Crea automaticamente un\'immagine Maxima ottimizzata.';
$string['inputtypematrix'] = 'Matrice';
$string['inputtypesinglechar'] = 'Singolo carattere';
$string['inputtypetextarea'] = 'Area di testo';
$string['inversetrig'] = 'Funzioni trigonometriche inverse';
$string['verifyquestionandupdate'] = 'Verifica il testo della domanda e aggiorna la finestra';
$string['youmustconfirm'] = 'Devi confermare qui.';
