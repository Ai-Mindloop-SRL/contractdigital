<?php
// ============================================
// FILL AND SIGN CONTRACT - ALL IN ONE PAGE
// Client fills data, reads contract, signs
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';

function getAppUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $script;
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../includes/ContractPDF.php';

// Helper function to get Romanian label for field
// COMPLETE Romanian labels for ALL fields (old + new)
function getFieldLabel($field_name) {

    $labels = [
        // NEW FIELDS - Child data
        'child_full_name' => 'Nume și prenume (copil)',
        'nume_copil' => 'Nume și prenume (copil)',
        'child_birth_date' => 'Data nașterii (copil)',
        'data_nastere_copil' => 'Data nașterii (copil)',
        'child_birth_place' => 'Locul nașterii (copil)',
        'loc_nastere_copil' => 'Locul nașterii (copil)',
        'sex_copil' => 'Sex (copil)',
        'cetatenie_copil' => 'Cetățenia (copil)',
        'nationalitate_copil' => 'Naționalitate (copil)',
        'cnp_copil' => 'CNP (copil)',
        
        // NEW FIELDS - Mother data (Mamă)
        'nume_mama' => 'Nume și prenume (mamă)',
        'nationalitate_mama' => 'Naționalitatea (mamă)',
        'cetatenie_mama' => 'Cetățenia (mamă)',
        'adresa_mama' => 'Adresa (mamă)',
        'telefon_mama' => 'Telefon (mamă)',
        'email_mama' => 'E-mail (mamă)',
        'loc_de_munca_mama' => 'Loc de muncă (mamă)',
        'loc_munca_mama' => 'Loc de muncă (mamă)',
        'functie_mama' => 'Funcția (mamă)',
        'tip_act_mama' => 'Tip act (mamă)',
        'serie_act_mama' => 'Serie (mamă)',
        'numar_act_mama' => 'Număr (mamă)',
        'data_emitere_mama' => 'Data emiterii (mamă)',
        'emis_de_mama' => 'Emis de (mamă)',
        'cnp_mama' => 'CNP (mamă)',
        
        // NEW FIELDS - Father data (Tată)
        'nume_tata' => 'Nume și prenume (tată)',
        'nationalitate_tata' => 'Naționalitatea (tată)',
        'cetatenie_tata' => 'Cetățenia (tată)',
        'adresa_tata' => 'Adresa (tată)',
        'telefon_tata' => 'Telefon (tată)',
        'email_tata' => 'E-mail (tată)',
        'loc_de_munca_tata' => 'Loc de muncă (tată)',
        'loc_munca_tata' => 'Loc de muncă (tată)',
        'functie_tata' => 'Funcția (tată)',
        'tip_act_tata' => 'Tip act (tată)',
        'serie_act_tata' => 'Serie (tată)',
        'numar_act_tata' => 'Număr (tată)',
        'data_emitere_tata' => 'Data emiterii (tată)',
        'emis_de_tata' => 'Emis de (tată)',
        'cnp_tata' => 'CNP (tată)',
        
        // ALIAS-URI pentru template (nomenclatură alternativă) - COMPLETE
        // MAMA - CI
        'seria_ci_mama' => 'Serie CI (mamă)',
        'nr_ci_mama' => 'Număr CI (mamă)',
        'data_emitere_ci_mama' => 'Data emitere CI (mamă)',
        'emitent_ci_mama' => 'Emitent CI (mamă)',
        // TATA - CI
        'seria_ci_tata' => 'Serie CI (tată)',
        'nr_ci_tata' => 'Număr CI (tată)',
        'data_emitere_ci_tata' => 'Data emitere CI (tată)',
        'emitent_ci_tata' => 'Emitent CI (tată)',
        // COPIL
        'data_nasterii_copil' => 'Data nașterii (copil)',
        // CONTACT URGENTA
        'nume_contact_urgenta' => 'Nume contact urgență',
        'adresa_contact_urgenta' => 'Adresă contact urgență',
        'telefon_contact_urgenta' => 'Telefon contact urgență',
        // FRATE/SORA
        'nume_frate_sora' => 'Nume frate/soră',
        'data_nasterii_frate_sora' => 'Data nașterii frate/soră',
        // INSTITUTIE
        'institutie_anterioara' => 'Instituție anterioară',
        'clasa_frecventata' => 'Clasă frecventată',
        'ultima_clasa_promovata' => 'Ultima clasă promovată',
        'perioada_institutie_anterioara' => 'Perioadă instituție anterioară',
        'tara_institutie_anterioara' => 'Țară instituție anterioară',
        // LIMBI
        'nivel_engleza' => 'Nivel engleză',
        'limba_vorbita_acasa' => 'Limba vorbită acasă',
        'limba_instruire_anterioara' => 'Limba de instruire anterioară',
        // ALTELE
        'data_contract' => 'Data contract',
        
        // NEW FIELDS - Emergency contact
        'nume_urgenta' => 'Nume contact urgență',
        'adresa_urgenta' => 'Adresă contact urgență',
        'telefon_urgenta' => 'Telefon contact urgență',
        
        // NEW FIELDS - Additional child info
        'dificultati_copil' => 'Dificultăți învățare/comportamentale (copil)',
        'hobby_copil' => 'Înclinații native și hobby-uri copil',
        'limba_instruire_institutie_anterioara' => 'Limba de instruire',
        'nume_frati' => 'Nume frați/surori',
        'data_nastere_frati' => 'Data naștere frați/surori',
        'd_nastere_frati' => 'Date naștere frați/surori (format: YYYY-MM-DD, separate prin virgulă)',
        
        // NEW FIELDS - Previous institution
        'nume_institutie_anterioara' => 'Nume instituție anterioară',
        'tara_institutie_anterioara' => 'Țara instituției',
        'perioada_institutie_anterioara' => 'Perioada',
        'clasa_institutie_anterioara' => 'Clasa frecventată',
        'ultima_clasa_promovata' => 'Ultima clasă promovată',
        'ultima_clasa_promovata_copil' => 'Ultima clasă promovată (copil)',
        
        // NEW FIELDS - Linguistic info
        'limba_institutie_anterioara' => 'Limba predare instituție anterioară',
        
        // NEW FIELDS - Program & Transport
        'program' => 'Selectează programul',
        'anexa_transport' => 'Opțiune transport',
        
        // NEW FIELDS - GDPR
        'gdpr_processing_consent' => 'Consimțământ prelucrare date',
        'gdpr_photo_video_consent' => 'Consimțământ foto/video',
        // ENGLISH field names - Child difficulties
        'child_speech_difficulties' => 'Dificultăți de vorbire',
        'child_behavior_difficulties' => 'Dificultăți comportamentale (copil)',
        'child_physical_difficulties' => 'Dificultăți fizice',
        'child_hobbies' => 'Hobby-uri copil',
        
        // ENGLISH field names - Emergency contact
        'emergency_contact_name' => 'Nume contact urgență',
        'emergency_contact_address' => 'Adresă contact urgență',
        'emergency_contact_phone' => 'Telefon contact urgență',
        
        // ENGLISH field names - Siblings
        'siblings_names' => 'Nume frați/surori',
        'siblings_birth_dates' => 'Date naștere frați/surori',
        
        // ENGLISH field names - Previous school
        'previous_school_name' => 'Instituție anterioară',
        'previous_school_country' => 'Țara instituției',
        'previous_school_period' => 'Perioada',
        'previous_school_class_attended' => 'Clasa frecventată',
        'previous_school_last_class_passed' => 'Ultima clasă promovată',
        
        // ENGLISH field names - Languages
        'home_language' => 'Limba vorbită acasă',
        'english_level' => 'Nivel limbă engleză',
        'previous_instruction_language' => 'Limba predare anterioară',
        
        // ENGLISH field names - Parent work
        'parent_workplace' => 'Loc de muncă (părinte)',
        'parent_job_title' => 'Funcția',
        'parent_1_job_title' => 'Funcția',
        'parent_2_job_title' => 'Funcția',
        
        // ENGLISH field names - Program
        'program_type' => 'Tip program',
        'transport_option' => 'Opțiune transport',
        // CHILD fields - ALL variations
        'child_firstname' => 'Prenume (copil)',
        'child_lastname' => 'Nume (copil)',
        'child_fullname' => 'Nume complet (copil)',
        'child_cnp' => 'CNP (copil)',
        'child_gender' => 'Sex (copil)',
        'child_citizenship' => 'Cetățenie (copil)',
        'child_nationality' => 'Naționalitate (copil)',
        'child_allergies' => 'Alergii copil',
        'child_special_diet' => 'Dietă specială copil',
        'child_group' => 'Grupă copil',
        
        // MOTHER fields - ALL variations
        'prenume_mama' => 'Prenume (mamă)',
        'nume_familie_mama' => 'Nume (mamă)',
        'mother_fullname' => 'Nume complet (mamă)',
        'mother_work_position' => 'Funcție (mamă)',
        'mother_id_type' => 'Tip act (mamă)',
        'mother_id_series' => 'Serie act (mamă)',
        'mother_id_number' => 'Număr act (mamă)',
        'mother_id_issue_date' => 'Data emitere act (mamă)',
        'mother_id_issued_by' => 'Emis de (mamă)',
        
        // FATHER fields - ALL variations
        'prenume_tata' => 'Prenume (tată)',
        'nume_familie_tata' => 'Nume (tată)',
        'father_fullname' => 'Nume complet (tată)',
        'father_work_position' => 'Funcție (tată)',
        'father_id_type' => 'Tip act (tată)',
        'father_id_series' => 'Serie act (tată)',
        'father_id_number' => 'Număr act (tată)',
        'father_id_issue_date' => 'Data emitere act (tată)',
        'father_id_issued_by' => 'Emis de (tată)',
        
        // CONTRACT fields
        'contract_number' => 'Număr contract',
        'contract_date' => 'Data contract',
        'contract_start_date' => 'Data început contract',
        'contract_end_date' => 'Data sfârșit contract',
        // Program Options (Anexe)
        'optiune_program_anexa1' => 'Opțiune Program (Anexa 1)',
        'optiune_program_anexa2' => 'Opțiune Program (Anexa 2)',
        'grupa_clasa' => 'GRUPA/CLASA',
        'program_optiune' => 'Opțiune Program',
        
        // Ensure these exist

    
];
    
    // Return Romanian label or fallback to formatted field name
    return $labels[$field_name] ?? ucfirst(str_replace('_', ' ', $field_name));
}
// Group by display_order ranges (simpler and more reliable)
// GRUPARE PE BAZĂ DE CUVINTE CHEIE în field_name
function getFieldGroup($field_name) {
    $name_lower = strtolower($field_name);
    
    // MAMĂ - mother, mama, _1
    if (strpos($name_lower, 'mother') !== false || 
        strpos($name_lower, 'mama') !== false || 
        strpos($name_lower, '_mama') !== false ||
        preg_match('/_1$/', $name_lower)) {
        return 'mama';
    }
    
    // TATĂ - father, tata, _2
    if (strpos($name_lower, 'father') !== false || 
        strpos($name_lower, 'tata') !== false || 
        strpos($name_lower, '_tata') !== false ||
        preg_match('/_2$/', $name_lower)) {
        return 'tata';
    }
    
    // COPIL - child, copil
    if (strpos($name_lower, 'child') !== false || 
        strpos($name_lower, 'copil') !== false ||
        strpos($name_lower, '_copil') !== false) {
        return 'copil';
    }
    
    // URGENȚĂ - emergency, urgenta, contact
    if (strpos($name_lower, 'emergency') !== false || 
        strpos($name_lower, 'urgenta') !== false ||
        strpos($name_lower, 'contact_urgenta') !== false) {
        return 'urgenta';
    }
    
    // INSTITUȚIE - previous, institution, institutie
    if (strpos($name_lower, 'previous') !== false || 
        strpos($name_lower, 'institution') !== false ||
        strpos($name_lower, 'institutie') !== false ||
        strpos($name_lower, 'scoala') !== false) {
        return 'institutie';
    }
    
    // LINGVISTIC - language, limba, nivel, engleza
    if (strpos($name_lower, 'language') !== false || 
        strpos($name_lower, 'limba') !== false ||
        strpos($name_lower, 'nivel') !== false ||
        strpos($name_lower, 'engleza') !== false) {
        return 'lingvistic';
    }
    
    // PROGRAM - program, transport, optiune
    if (strpos($name_lower, 'program') !== false || 
        strpos($name_lower, 'transport') !== false ||
        strpos($name_lower, 'optiune') !== false) {
        return 'program';
    }
    
    // GDPR - gdpr, consent, acord
    if (strpos($name_lower, 'gdpr') !== false || 
        strpos($name_lower, 'consent') !== false ||
        strpos($name_lower, 'acord') !== false ||
        strpos($name_lower, 'foto') !== false) {
        return 'gdpr';
    }
    
    return 'altele';
}


$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('❌ Token invalid');
}

$conn = getDBConnection();

// Get contract by token
$stmt = $conn->prepare("SELECT * FROM contracts WHERE signing_token = ? OR unique_token = ?");
$stmt->bind_param("ss", $token, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die('❌ Contract invalid sau expirat');
}

$contract = $result->fetch_assoc();
$contract_id = $contract['id'];
$template_id = $contract['template_id'];
$stmt->close();

// Check if already signed
if ($contract['status'] == 'signed') {
    echo "<div style='text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: orange;'>⚠️ Contract deja semnat</h2>";
    echo "<p>Semnat la: <strong>" . date('d.m.Y H:i', strtotime($contract['signed_at'])) . "</strong></p>";
    if (!empty($contract['pdf_path'])) {
        echo "<p><a href='" . $contract['pdf_path'] . "' target='_blank'>📄 Descarcă PDF</a></p>";
    }
    echo "</div>";
    exit;
}

// Get template content to check available options
$stmt = $conn->prepare("SELECT template_content FROM contract_templates WHERE id = ?");
$stmt->bind_param("i", $template_id);
$stmt->execute();
$template_result = $stmt->get_result();
$template_data = $template_result->fetch_assoc();
$template_content = $template_data['template_content'] ?? '';
$stmt->close();

// Check if template has "Program mediu" option
$has_program_mediu = strpos($template_content, 'Program mediu:') !== false;

// Get template fields (NEW: using normalized structure)
$stmt = $conn->prepare("
    SELECT 
        fd.field_name,
        fd.field_type,
        COALESCE(tfm.custom_label, fd.field_label) as field_label,
        COALESCE(tfm.custom_placeholder, fd.placeholder) as placeholder,
        fd.field_group,
        tfm.is_required,
        tfm.display_order,
        fd.validation_rules,
        '' as default_value
    FROM template_field_mapping tfm
    INNER JOIN field_definitions fd ON tfm.field_definition_id = fd.id
    WHERE tfm.template_id = ?
    ORDER BY tfm.display_order
");
$stmt->bind_param("i", $template_id);
$stmt->execute();
$fields_result = $stmt->get_result();
$fields = $fields_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sort by display_order (set in database)
// SORTARE: GRUPARE PE SECȚIUNI (keywords), apoi ORDONARE pe display_order în fiecare secȚiune
usort($fields, function($a, $b) {
    // Determină secțiunea pentru fiecare câmp
    $section_a = getFieldGroup($a['field_name']);
    $section_b = getFieldGroup($b['field_name']);
    
    // Ordinea secțiunilor (prioritate)
    $section_order = [
        'mama' => 1,
        'tata' => 2,
        'copil' => 3,
        'urgenta' => 4,
        'institutie' => 5,
        'lingvistic' => 6,
        'program' => 7,
        'gdpr' => 8,
        'altele' => 9
    ];
    
    $priority_a = $section_order[$section_a] ?? 99;
    $priority_b = $section_order[$section_b] ?? 99;
    
    // Sortare primă: pe SECȚIUNE (grup)
    if ($priority_a != $priority_b) {
        return $priority_a - $priority_b;
    }
    
    // Sortare secundară: pe display_order În ACEEASI SECȚIUNE
    $order_a = $a['display_order'] ?? 0;
    $order_b = $b['display_order'] ?? 0;
    
    if ($order_a != $order_b) {
        return $order_a - $order_b;
    }
    
    // Sortare terțiară: pe field_name dacă display_order este identic
    return strcmp($a['field_name'], $b['field_name']);
});

// HANDLE FORM SUBMISSION (Fill + Sign)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $signature_data = $_POST['signature_data'] ?? '';
    $recipient_phone = $_POST['phone_number'] ?? '';
    
    if (empty($signature_data) || $signature_data == 'data:,') {
        die('❌ Vă rugăm să semnați contractul');
    }
    
    // Build contract content by replacing placeholders
    $contract_content = $contract['contract_content'] ?? '';
    
    // Auto-generate contract number and date
    $numar_contract = 'CT-' . date('Y') . '-' . str_pad($contract_id, 5, '0', STR_PAD_LEFT);
    $data_contract = date('d.m.Y');
    
    // Replace auto-generated fields
    $contract_content = str_replace('[NUMAR_CONTRACT]', $numar_contract, $contract_content);
    $contract_content = str_replace('[DATA_CONTRACT]', $data_contract, $contract_content);
    
    foreach ($fields as $field) {
        $field_name = $field['field_name'];
        $field_value = $_POST['field_' . $field_name] ?? ($field['default_value'] ?? '');
        
        // Replace [FIELD_NAME] with value
        $contract_content = str_replace('[' . strtoupper($field_name) . ']', $field_value, $contract_content);
        
        // Save field value
        $stmt = $conn->prepare("INSERT INTO contract_field_values (contract_id, field_name, field_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE field_value = ?");
        $stmt->bind_param("isss", $contract_id, $field_name, $field_value, $field_value);
        $stmt->execute();
        $stmt->close();
    }
    
    // Add signatures at 3 locations
    if (!empty($signature_data)) {
        $signature_html = '<div style="margin: 15px 0 10px 0; page-break-inside: avoid;">';
        $signature_html .= '<img src="' . $signature_data . '" style="max-width: 200px; height: auto; border: 1px solid #333;" />';
        $signature_html .= '<p style="margin: 3px 0; font-size: 9pt;"><em>Semnătură electronică - ' . date('d.m.Y H:i') . '</em></p>';
        $signature_html .= '</div>';
        
        // Find all signature locations
        $positions = array();
        
        // 1. Find 2x REPREZENTANŢII LEGALI (9.13 and ANEXA 1)
        $pattern1 = '/(<strong>REPREZENTAN[TŢ]II LEGALI<\/strong>)/iu';
        preg_match_all($pattern1, $contract_content, $matches1, PREG_OFFSET_CAPTURE);
        
        // 2. Find SEMNĂTURĂ PĂRINTE / TUTORE COPIL (ANEXA 2)
        $pattern2 = '/(<strong>SEMNĂTUR[AĂ] PĂRINTE \/ TUTORE COPIL<\/strong>)/iu';
        preg_match_all($pattern2, $contract_content, $matches2, PREG_OFFSET_CAPTURE);
        
        // Collect all positions
        if (isset($matches1[0][0])) {
            $positions[] = array('pos' => $matches1[0][0][1] + strlen($matches1[0][0][0]), 'order' => 1);
        }
        if (isset($matches1[0][1])) {
            $positions[] = array('pos' => $matches1[0][1][1] + strlen($matches1[0][1][0]), 'order' => 2);
        }
        if (isset($matches2[0][0])) {
            $positions[] = array('pos' => $matches2[0][0][1] + strlen($matches2[0][0][0]), 'order' => 3);
        }
        
        // Sort by position DESC (work backwards to preserve positions)
        usort($positions, function($a, $b) {
            return $b['pos'] - $a['pos'];
        });
        
        // Insert signatures at all positions
        foreach ($positions as $pos_data) {
            $contract_content = substr_replace($contract_content, $signature_html, $pos_data['pos'], 0);
        }
    }
    
    // Update contract
    $stmt = $conn->prepare("UPDATE contracts SET contract_content = ?, status = 'signed', signed_at = NOW(), signature_data = ?, recipient_phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $contract_content, $signature_data, $recipient_phone, $contract_id);
    $stmt->execute();
    $stmt->close();
    
    // Reload contract data
    $stmt = $conn->prepare("SELECT * FROM contracts WHERE id = ?");
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contract = $result->fetch_assoc();
    $stmt->close();
    
    // Generate PDF
    try {
        // Process checkboxes in contract content for PDF
        // Find both GDPR sections and replace specifically
        $gdpr_processing = $_POST['field_gdpr_processing_consent'] ?? '';
        $gdpr_photo = $_POST['field_gdpr_photo_video_consent'] ?? '';
        
        // DEBUG: Log GDPR values
        error_log("GDPR DEBUG - Processing: '" . $gdpr_processing . "' (type: " . gettype($gdpr_processing) . ")");
        error_log("GDPR DEBUG - Photo: '" . $gdpr_photo . "' (type: " . gettype($gdpr_photo) . ")");
        
        // Try first with 9.3/9.4 (old templates)
        $sections = preg_split('/(<strong>9\.[34]<\/strong>)/', $contract_content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        if (count($sections) >= 5) {
            // Section 9.3 - processing consent (index 2)
            if ($gdpr_processing == 'DA') {
                $sections[2] = preg_replace('/DA ☐/', 'DA ☑', $sections[2], 1);
            } else if ($gdpr_processing == 'NU') {
                $sections[2] = preg_replace('/NU ☐/', 'NU ☑', $sections[2], 1);
            }
            
            // Section 9.4 - photo/video consent (index 4)
            if ($gdpr_photo == 'DA') {
                $sections[4] = preg_replace('/DA ☐/', 'DA ☑', $sections[4], 1);
            } else if ($gdpr_photo == 'NU') {
                $sections[4] = preg_replace('/NU ☐/', 'NU ☑', $sections[4], 1);
            }
            
            $contract_content = implode('', $sections);
        } else {
            // If 9.3/9.4 not found, try with 8.3/8.4 (new templates)
            $sections = preg_split('/(<strong>8\.[345]<\/strong>)/', $contract_content, -1, PREG_SPLIT_DELIM_CAPTURE);
            
            // DEBUG: Log section count
            error_log("GDPR DEBUG - 8.3/8.4 sections count: " . count($sections));
            
            if (count($sections) >= 5) {
                // Section 8.3 - processing consent (between 8.3 and 8.4)
                // Find the section between 8.3 and 8.4
                error_log("GDPR DEBUG - Section [2] contains DA ☐: " . (strpos($sections[2], 'DA ☐') !== false ? 'YES' : 'NO'));
                if ($gdpr_processing == 'DA') {
                    $sections[2] = preg_replace('/DA ☐/', 'DA ☑', $sections[2], 1);
                    error_log("GDPR DEBUG - Replaced 8.3 with DA ☑");
                } else if ($gdpr_processing == 'NU') {
                    $sections[2] = preg_replace('/NU ☐/', 'NU ☑', $sections[2], 1);
                    error_log("GDPR DEBUG - Replaced 8.3 with NU ☑");
                }
                
                // Section 8.4 - photo/video consent (between 8.4 and 8.5)
                if ($gdpr_photo == 'DA') {
                    $sections[4] = preg_replace('/DA ☐/', 'DA ☑', $sections[4], 1);
                } else if ($gdpr_photo == 'NU') {
                    $sections[4] = preg_replace('/NU ☐/', 'NU ☑', $sections[4], 1);
                }
                
                $contract_content = implode('', $sections);
            }
        }


        
        // Program scurt/mediu/lung
        $program_anexa1 = $_POST['field_optiune_program_anexa1'] ?? '';
        $program_anexa2 = $_POST['field_optiune_program_anexa2'] ?? '';
        
        if ($program_anexa1 == 'scurt' || $program_anexa2 == 'scurt') {
            $contract_content = str_replace('☐ <strong>Program scurt:</strong>', '☑ <strong>Program scurt:</strong>', $contract_content);
        }
        if ($program_anexa1 == 'mediu' || $program_anexa2 == 'mediu') {
            $contract_content = str_replace('☐ <strong>Program mediu:</strong>', '☑ <strong>Program mediu:</strong>', $contract_content);
        }
        if ($program_anexa1 == 'lung' || $program_anexa2 == 'lung') {
            $contract_content = str_replace('☐ <strong>Program lung:</strong>', '☑ <strong>Program lung:</strong>', $contract_content);
        }
        
        // Process grupa_clasa checkboxes for Template 7
        $grupa_clasa = $_POST['field_grupa_clasa'] ?? '';
        
        if ($grupa_clasa == 'mica') {
            $contract_content = str_replace('☐ <strong>Grupa mica: 3 - 4 ani</strong>', '☑ <strong>Grupa mica: 3 - 4 ani</strong>', $contract_content);
        }
        if ($grupa_clasa == 'mijlocie') {
            $contract_content = str_replace('☐ <strong>Grupa mijlocie: 4 - 5 ani</strong>', '☑ <strong>Grupa mijlocie: 4 - 5 ani</strong>', $contract_content);
        }
        if ($grupa_clasa == 'mare') {
            $contract_content = str_replace('☐ <strong>Grupa mare: 5 - 6 ani</strong>', '☑ <strong>Grupa mare: 5 - 6 ani</strong>', $contract_content);
        }
        

        // Create PDF with correct constructor
        $pdf = new ContractPDF($contract_id);
        
        // Generate PDF with processed content
        $contract['contract_content'] = $contract_content;
        $pdf->generate($contract);
        $pdf_filename = 'contract_' . $contract_id . '_' . date('YmdHis') . '.pdf';
        $pdf_path = __DIR__ . '/uploads/contracts/' . $pdf_filename;
        
        if (!is_dir(__DIR__ . '/uploads/contracts/')) {
            mkdir(__DIR__ . '/uploads/contracts/', 0755, true);
        }
        
        $pdf->Output($pdf_path, 'F');
        
        $pdf_url = getAppUrl() . '/uploads/contracts/' . $pdf_filename;
        
        // Manual reconnect after ContractPDF closes connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $stmt = $conn->prepare("UPDATE contracts SET pdf_path = ? WHERE id = ?");
        $stmt->bind_param("si", $pdf_url, $contract_id);
        $stmt->execute();
        $stmt->close();
        
        // Send email with PHPMailer
        $to = $contract['recipient_email'];
        $subject = 'Contract semnat - ' . $numar_contract;
        
        $message = '<html><body>';
        $message .= '<h2>Contract semnat cu succes</h2>';
        $message .= '<p>Contractul dumneavoastră a fost semnat.</p>';
        $message .= '<p><strong>Număr contract:</strong> ' . $numar_contract . '</p>';
        $message .= '<p><strong>Data:</strong> ' . $data_contract . '</p>';
        $message .= '<p><a href="' . $pdf_url . '" style="display: inline-block; padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;">Descarcă PDF</a></p>';
        $message .= '</body></html>';
        
        // Attach PDF
        $attachments = [
            [
                'path' => $pdf_path,
                'filename' => 'contract_' . $numar_contract . '.pdf'
            ]
        ];
        
        $mail_result = sendEmail($to, $subject, $message, 'office@splm.ro', null, $attachments);
        error_log("Email sent to: $to, Result: " . ($mail_result ? "SUCCESS" : "FAILED"));
        
        echo "<div style='text-align: center; margin-top: 50px;'>";
        echo "<h2 style='color: green;'>✅ Contract semnat!</h2>";
        echo "<p><a href='" . $pdf_url . "' target='_blank' style='display: inline-block; padding: 15px 30px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px;'>📄 Descarcă PDF</a></p>";
        echo "</div>";
        exit;
        
    } catch (Exception $e) {
        die('❌ Eroare la generarea PDF: ' . $e->getMessage());
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completare și Semnare Contract</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; }
        .section { margin-bottom: 30px; padding: 25px; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #3498db; }
        .section h2 { color: #2c3e50; margin-bottom: 20px; font-size: 20px; }
        .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: 500; font-size: 14px; }
        input[type="text"], input[type="tel"], input[type="date"], textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus, textarea:focus { outline: none; border-color: #3498db; }
        textarea { resize: vertical; min-height: 80px; font-family: inherit; }
        
        /* AGGRESSIVE OVERRIDE - Remove extra spacing */
        .contract-preview p[style*="text-align: center"],
        .contract-preview p[style*="text-align:center"] {
            text-align: left !important;
            padding: 0 8px !important;
        }
        
        .contract-preview p[style*="text-align: justify"],
        .contract-preview p[style*="text-align:justify"] {
            text-align: left !important;
            padding: 0 8px !important;
        }
        
        /* Remove any inline padding/margin from template */
        .contract-preview p[style],
        .contract-preview div[style],
        .contract-preview span[style] {
            padding: 0 8px !important;
            margin: 6px 0 !important;
        }
        
        /* Compact all spacing aggressively */
        .contract-preview {
            font-size: 13px !important;
            line-height: 1.35 !important;
        }
        

        /* Override template's .contract-container padding */
        .contract-preview .contract-container {
            padding: 10px 12px !important;
            max-width: 100% !important;
            margin: 0 !important;
        }
        
        .contract-preview body {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        @media (max-width: 768px) {
            .contract-preview .contract-container {
                padding: 8px 10px !important;
            }
        }
        
        @media (max-width: 480px) {
            .contract-preview .contract-container {
                padding: 6px 8px !important;
            }
        }

        .contract-preview { background: white; padding: 25px; border-radius: 8px; max-height: 400px; overflow-y: auto; border: 2px solid #ddd; margin-bottom: 20px; }

        /* Contract preview text optimization */
        .contract-preview p { 
            margin: 8px 0; 
            line-height: 1.4;
        }
        
        .contract-preview h1, 
        .contract-preview h2, 
        .contract-preview h3 { 
            margin: 12px 0 8px 0;
            line-height: 1.3;
        }
        
        .contract-preview table { 
            width: 100%; 
            margin: 10px 0;
        }
        
        .contract-preview ul, 
        .contract-preview ol { 
            margin: 8px 0;
            padding-left: 25px;
        }
        

        .signature-section { background: #fff3cd; padding: 25px; border-radius: 8px; border: 2px solid #ffc107; }
        .signature-pad { border: 2px solid #333; border-radius: 5px; background: white; cursor: crosshair; display: block; margin: 20px auto; }
        .button-group { text-align: center; margin-top: 20px; }
        .btn { padding: 12px 30px; margin: 0 10px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .btn-clear { background: #e74c3c; color: white; }
        .btn-sign { background: #27ae60; color: white; font-size: 18px; padding: 15px 50px; }
        .btn-clear:hover { background: #c0392b; }
        .btn-sign:hover { background: #229954; }
        
        /* Radio buttons styling */
        .radio-group { display: flex; flex-direction: column; gap: 15px; margin-top: 10px; }
        .radio-label { display: block; padding: 15px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; }
        .radio-label:hover { border-color: #3498db; background-color: #f8f9fa; }
        .radio-label:has(input[type="radio"]:checked) { border-color: #3498db; background-color: #e7f3ff; }
        .radio-label strong { display: block; margin-bottom: 5px; color: #333; }
        .radio-label input[type="radio"] { margin-right: 10px; width: 18px; height: 18px; vertical-align: middle; }
        .radio-label p { margin: 5px 0 0 28px; color: #666; font-size: 13px; line-height: 1.4; }
        
        /* Radio buttons inline (pentru DA/NU) */
        .radio-group-inline { display: flex; gap: 30px; margin-top: 10px; }
        .radio-label-inline { display: flex; align-items: center; cursor: pointer; font-size: 16px; font-weight: 600; padding: 10px 20px; border: 2px solid #ddd; border-radius: 5px; transition: all 0.3s ease; }
        .radio-label-inline:hover { border-color: #3498db; background-color: #f8f9fa; }
        .radio-label-inline:has(input[type="radio"]:checked) { border-color: #27ae60; background-color: #d4edda; }
        .radio-label-inline input[type="radio"] { margin-right: 8px; width: 18px; height: 18px; }
        
        /* Radio buttons pentru program - layout în grid */
        .radio-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 10px; }
        .radio-grid .radio-label { min-width: 300px; }
        
        .optional { color: #999; font-size: 13px; font-weight: normal; }
        .required { color: red; }
        
        @media (max-width: 768px) { .field-grid { grid-template-columns: 1fr; } }
        .instructions { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #3498db; }
        
        /* Section Separators */
        .section-separator {
            grid-column: 1 / -1;
            margin: 30px 0 20px 0;
            padding: 15px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .section-title {
            color: white;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: white;
            border-radius: 2px;
        }

        /* ============================================ */
        /* MOBILE RESPONSIVE STYLES */
        /* ============================================ */
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            
            .container { border-radius: 5px; }
            
            .header { padding: 20px 15px; }
            .header h1 { font-size: 20px; }
            
            .content { padding: 15px; }
            
            .section { padding: 15px; margin-bottom: 20px; }
            .section h2 { font-size: 18px; margin-bottom: 15px; }
            
            .field-grid { grid-template-columns: 1fr; gap: 15px; }
            
            .form-group { margin-bottom: 12px; }
            
            label { font-size: 13px; }
            
            input[type="text"], 
            input[type="tel"], 
            input[type="date"], 
            textarea { 
                padding: 10px; 
                font-size: 16px; /* Prevent zoom on iOS */
            }
            
            
        /* AGGRESSIVE OVERRIDE - Remove extra spacing */
        .contract-preview p[style*="text-align: center"],
        .contract-preview p[style*="text-align:center"] {
            text-align: left !important;
            padding: 0 8px !important;
        }
        
        .contract-preview p[style*="text-align: justify"],
        .contract-preview p[style*="text-align:justify"] {
            text-align: left !important;
            padding: 0 8px !important;
        }
        
        /* Remove any inline padding/margin from template */
        .contract-preview p[style],
        .contract-preview div[style],
        .contract-preview span[style] {
            padding: 0 8px !important;
            margin: 6px 0 !important;
        }
        
        /* Compact all spacing aggressively */
        .contract-preview {
            font-size: 13px !important;
            line-height: 1.35 !important;
        }
        

        /* Override template's .contract-container padding */
        .contract-preview .contract-container {
            padding: 10px 12px !important;
            max-width: 100% !important;
            margin: 0 !important;
        }
        
        .contract-preview body {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        @media (max-width: 768px) {
            .contract-preview .contract-container {
                padding: 8px 10px !important;
            }
        }
        
        @media (max-width: 480px) {
            .contract-preview .contract-container {
                padding: 6px 8px !important;
            }
        }

        .contract-preview { 
                padding: 10px !important; 
                max-height: 300px; 
                font-size: 13px !important;
                line-height: 1.3 !important;
            }
            
            .contract-preview p { 
                margin: 5px 0 !important;
                padding: 0 3px !important;
            }
            
            .contract-preview h1, 
            .contract-preview h2 { 
                margin: 8px 0 5px 0 !important;
                font-size: 14px !important;
            }
            
            .signature-section { padding: 15px; }
            
            .signature-pad { 
                width: 100% !important; 
                height: 200px !important;
                touch-action: none;
            }
            
            .button-group { 
                display: flex; 
                flex-direction: column; 
                gap: 10px;
            }
            
            .btn { 
                width: 100%; 
                margin: 0;
                padding: 14px 20px;
                font-size: 16px;
            }
            
            .btn-sign { 
                padding: 16px 20px;
                font-size: 18px;
            }
            
            /* Radio buttons mobile */
            .radio-group { gap: 10px; }
            
            .radio-label { 
                padding: 12px; 
                font-size: 14px;
            }
            
            .radio-group-inline { 
                flex-direction: column; 
                gap: 10px;
            }
            
            .radio-label-inline { 
                width: 100%;
                justify-content: center;
            }
            
            .radio-grid { 
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .radio-grid .radio-label { 
                min-width: auto;
            }
            
            /* Section separators mobile */
            .section-separator { 
                margin: 20px 0 15px 0;
                padding: 12px 15px;
            }
            
            .section-title { 
                font-size: 16px;
                letter-spacing: 0.5px;
            }
            
            .section-title::before { 
                height: 20px;
            }
            
            /* Instructions mobile */
            .instructions { 
                padding: 12px;
                font-size: 14px;
            }
            
            .instructions ol { 
                margin-left: 15px;
                font-size: 13px;
            }
            
            /* Auto-save notification mobile */
            .instructions + div[style*="background: #e8f5e9"] {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px;
            }
            
            .instructions + div button {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 { font-size: 18px; }
            
            .section h2 { font-size: 16px; }
            
            
        /* AGGRESSIVE OVERRIDE - Remove extra spacing */
        .contract-preview p[style*="text-align: center"],
        .contract-preview p[style*="text-align:center"] {
            text-align: left !important;
            padding: 0 8px !important;
        }
        
        .contract-preview p[style*="text-align: justify"],
        .contract-preview p[style*="text-align:justify"] {
            text-align: left !important;
            padding: 0 8px !important;
        }
        
        /* Remove any inline padding/margin from template */
        .contract-preview p[style],
        .contract-preview div[style],
        .contract-preview span[style] {
            padding: 0 8px !important;
            margin: 6px 0 !important;
        }
        
        /* Compact all spacing aggressively */
        .contract-preview {
            font-size: 13px !important;
            line-height: 1.35 !important;
        }
        

        /* Override template's .contract-container padding */
        .contract-preview .contract-container {
            padding: 10px 12px !important;
            max-width: 100% !important;
            margin: 0 !important;
        }
        
        .contract-preview body {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        @media (max-width: 768px) {
            .contract-preview .contract-container {
                padding: 8px 10px !important;
            }
        }
        
        @media (max-width: 480px) {
            .contract-preview .contract-container {
                padding: 6px 8px !important;
            }
        }

        .contract-preview { 
                max-height: 250px;
                font-size: 12px !important;
                padding: 8px !important;
            }
            
            .contract-preview p { 
                margin: 4px 0 !important;
                padding: 0 2px !important;
            }
            
            .signature-pad { 
                height: 180px !important;
            }
            
            input[type="text"], 
            input[type="tel"], 
            input[type="date"] {
                font-size: 16px; /* Prevent zoom */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Completare și Semnare Contract</h1>
        </div>
        
        <div class="content">
            <div class="instructions">
                <strong>Instrucțiuni:</strong>
                <ol style="margin-left: 20px; margin-top: 10px;">
                    <li>Completați datele în formularul de mai jos</li>
                    <li>Citiți contractul complet</li>
                    <li>Semnați în caseta de mai jos</li>
                    <li>Apăsați butonul "Semnează și Trimite"</li>
                </ol>
            </div>
            
            <form method="POST" id="mainForm">
                
                <!-- CONTACT INFO 
                <div class="section">
                    <h2>📧 Date de Contact</h2>
                    <div class="field-grid">
                        <div class="form-group">
                            <label>Număr Telefon <span class="required">*</span></label>
                            <input type="tel" name="phone_number" required placeholder="Ex: 0712345678">
                        </div>
                    </div>
                </div>-->
                
                <!-- CONTRACT FIELDS -->
                <!-- CONTRACT FIELDS -->
                <div class="section">
                    <h2>📝 Completează Datele pentru Contract</h2>
                    
                    <!-- Auto-save notification and controls -->
                    <div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 20px;">💾</span>
                            <div>
                                <strong style="color: #2e7d32;">Auto-salvare activată</strong>
                                <p style="margin: 2px 0 0 0; font-size: 13px; color: #555;">Datele tale sunt salvate automat în browser. <span id="lastSaveTime" style="color: #666;"></span></p>
                            </div>
                        </div>
                        <button type="button" id="clearSavedData" style="background: #f44336; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 13px; white-space: nowrap;">
                            🗑️ Șterge datele salvate
                        </button>
                    </div>
                    <div class="field-grid">
                        <?php 
                        $current_section = '';
                        $section_titles = [
                            'mama' => '👩 DATE (mamă)',
                            'tata' => '👨 DATE (tată)',
                            'copil' => '👶 DATE (copil)',
                            'urgenta' => '🚨 CONTACT URGENȚĂ',
                            'copil_info' => '📚 INFORMAȚII SUPLIMENTARE (copil)',
                            'institutie' => '🏫 INSTITUȚIE ANTERIOARĂ',
                            'lingvistic' => '🗣️ DATE LINGVISTICE',
                            'program' => '🚌 PROGRAM ȘI TRANSPORT',
                            'gdpr' => '🔒 CONSIMȚĂMINTE GDPR'
                        ];
                        
                        foreach ($fields as $field): 
                            $field_name = $field['field_name'];
                            $field_type = $field['field_type'];
                            $is_optional = !$field['is_required'];
                            $field_label = getFieldLabel($field_name);
                            $default_value = htmlspecialchars($field['default_value'] ?? '');
                            $field_placeholder = htmlspecialchars($field['placeholder'] ?? $field_label);
                            
                            // Detect section change
                            $section = getFieldGroup($field['field_name']);
                            if ($section != $current_section) {
                                if ($current_section != '') {
                                    echo '</div></div>'; // Close previous grid and section
                                }
                                $current_section = $section;
                                $section_title = $section_titles[$section] ?? 'ALTE DATE';
                                echo '<div class="section-separator">';
                                echo '<h3 class="section-title">' . $section_title;
                                
                                // Add checkboxes for mama/tata sections (can be unchecked)
                                if ($section == 'mama') {
                                    echo '<label style="margin-left: 20px; font-weight: normal; font-size: 0.9em; color: white;">';
                                    echo '<input type="checkbox" id="no_mama_data" style="margin-right: 5px;"> Fără date mamă';
                                    echo '</label>';
                                } elseif ($section == 'tata') {
                                    echo '<label style="margin-left: 20px; font-weight: normal; font-size: 0.9em; color: white;">';
                                    echo '<input type="checkbox" id="no_tata_data" style="margin-right: 5px;"> Fără date tată';
                                    echo '</label>';
                                }
                                
                                echo '</h3>';
                                echo '</div>';
                                echo '<div class="section"><div class="field-grid">';
                            }
                        ?>
                            
                            <div class="form-group <?php echo (strpos($field_name, 'dificultati') !== false || strpos($field_name, 'program') !== false || strpos($field_name, 'anexa') !== false || strpos($field_name, 'frati') !== false) ? 'full-width' : ''; ?>">
                                <label>
                                    <?php echo $field_label; ?>
                                    <?php if ($is_optional): ?>
                                        <span class="optional">(opțional)</span>
                                    <?php else: ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                
                                <?php if ($field_type == 'radio'): ?>
                                    
                                    <?php if (strpos($field_name, 'gdpr') !== false): ?>
                                        <!-- GDPR Radio Buttons (DA/NU) -->
                                        <div class="radio-group-inline">
                                            <label class="radio-label-inline">
                                                <input type="radio" name="field_<?php echo $field_name; ?>" value="DA" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_<?php echo strtoupper($field_name); ?>"> DA
                                            </label>
                                            <label class="radio-label-inline">
                                                <input type="radio" name="field_<?php echo $field_name; ?>" value="NU" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_<?php echo strtoupper($field_name); ?>"> NU
                                            </label>
                                        </div>
                                        
                                    <?php elseif ($field_name == 'program'): ?>
                                        <!-- Program Radio Buttons -->
                                        <div class="radio-group">
                                            <label class="radio-label">
                                                <input type="radio" name="field_program" value="scurt" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_PROGRAM">
                                                <strong>Program scurt (08:30-13:00)</strong>
                                                <p>Opțiune de mijloc pentru copiii preșcolari care doresc să participe la activitățile școlare pentru o perioadă mai scurtă.</p>
                                            </label>
                                            <?php if ($has_program_mediu): ?>
                                            <label class="radio-label">
                                                <input type="radio" name="field_program" value="mediu" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_PROGRAM">
                                                <strong>Program mediu (08:30-16:00)</strong>
                                                <p>Această opțiune este similară cu programul zilnic obișnuit, cu excepția faptului că părinții își pot prelua copiii până la ora 16:00.</p>
                                            </label>
                                            <?php endif; ?>
                                            <label class="radio-label">
                                                <input type="radio" name="field_program" value="lung" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_PROGRAM">
                                                <strong>Program lung (08:30-18:00)</strong>
                                                <p>Această opțiune este similară cu programul zilnic obișnuit și oferă o prelungire a perioadei de activități în sala de clasă, precum și a momentelor de joacă liberă în curtea școlii.</p>
                                            </label>
                                        </div>
                                        
                                    <?php elseif ($field_name == 'anexa_transport'): ?>
                                        <!-- Anexa Transport Radio Buttons -->
                                        <div class="radio-group">
                                            <label class="radio-label">
                                                <input type="radio" name="field_anexa_transport" value="dus" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_ANEXA_TRANSPORT">
                                                <strong>ANEXĂ 1 - Transport Școlar de Dimineață (DOAR DUS)</strong>
                                                <p>Programul de Transport Școlar de Dimineață</p>
                                            </label>
                                            <label class="radio-label">
                                                <input type="radio" name="field_anexa_transport" value="dus_intors" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_ANEXA_TRANSPORT">
                                                <strong>ANEXĂ 2 - Transport Școlar Complet (DUS-ÎNTORS)</strong>
                                                <p>Programul de Transport Școlar Complet</p>
                                            </label>
                                        </div>
                                    
                                    <?php elseif ($field_name == 'grupa_clasa'): ?>
                                        <div class="radio-group">
                                            <label class="radio-label">
                                                <input type="radio" name="field_grupa_clasa" value="mica" required>
                                                <strong>Grupa mica: 3 - 4 ani</strong>
                                            </label>
                                            <label class="radio-label">
                                                <input type="radio" name="field_grupa_clasa" value="mijlocie" required>
                                                <strong>Grupa mijlocie: 4 - 5 ani</strong>
                                            </label>
                                            <label class="radio-label">
                                                <input type="radio" name="field_grupa_clasa" value="mare" required>
                                                <strong>Grupa mare: 5 - 6 ani</strong>
                                            </label>
                                        </div>
                                    <?php elseif ($field_name == 'optiune_program_anexa1' || $field_name == 'optiune_program_anexa2'): ?>
                                        <!-- Opțiune Program Anexa 1/2 Radio Buttons - TEXT SIMPLU -->
                                        <div class="radio-group">
                                            <label class="radio-label">
                                                <input type="radio" name="field_<?php echo $field_name; ?>" value="scurt" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_<?php echo strtoupper($field_name); ?>">
                                                <strong>Program scurt</strong>
                                                <p>Detaliile programului apar în contractul final</p>
                                            </label>
                                            <?php if ($has_program_mediu): ?>
                                            <label class="radio-label">
                                                <input type="radio" name="field_<?php echo $field_name; ?>" value="mediu" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_<?php echo strtoupper($field_name); ?>">
                                                <strong>Program mediu</strong>
                                                <p>Detaliile programului apar în contractul final</p>
                                            </label>
                                            <?php endif; ?>
                                            <label class="radio-label">
                                                <input type="radio" name="field_<?php echo $field_name; ?>" value="lung" <?php echo !$is_optional ? 'required' : ''; ?> class="contract-field" data-field="FIELD_<?php echo strtoupper($field_name); ?>">
                                                <strong>Program lung</strong>
                                                <p>Detaliile programului apar în contractul final</p>
                                            </label>
                                        </div>
                                    
                                    <?php else: ?>
                                        <!-- Generic Radio Buttons (for any other field_type='radio') -->
                                        <?php
                                        // Detectează dacă e câmp de program (are multe opțiuni lungi)
                                        $is_program_field = (strpos($field_name, 'program') !== false || strpos($field_name, 'anexa') !== false);
                                        $container_class = $is_program_field ? 'radio-grid' : 'radio-group-inline';
                                        $label_class = $is_program_field ? 'radio-label' : 'radio-label-inline';
                                        ?>
                                        <div class="<?php echo $container_class; ?>">
                                            <?php
                                            // Try to parse options from default_value (format: "val1:Label1|val2:Label2")
                                            $radio_options = [];
                                            
                                            if (!empty($default_value) && strpos($default_value, '|') !== false) {
                                                $pairs = explode('|', $default_value);
                                                foreach ($pairs as $pair) {
                                                    if (strpos($pair, ':') !== false) {
                                                        list($val, $lbl) = explode(':', $pair, 2);
                                                        $radio_options[] = ['value' => trim($val), 'label' => trim($lbl)];
                                                    }
                                                }
                                            }
                                            
                                            // Default to DA/NU if no options defined
                                            if (empty($radio_options)) {
                                                $radio_options = [
                                                    ['value' => 'DA', 'label' => '✅ DA'],
                                                    ['value' => 'NU', 'label' => '❌ NU']
                                                ];
                                            }
                                            
                                            foreach ($radio_options as $option):
                                            ?>
                                                <label class="<?php echo $label_class; ?>">
                                                    <input type="radio" 
                                                           name="field_<?php echo $field_name; ?>" 
                                                           value="<?php echo htmlspecialchars($option['value']); ?>" 
                                                           <?php echo !$is_optional ? 'required' : ''; ?>
                                                           class="contract-field"
                                                           data-field="FIELD_<?php echo strtoupper($field_name); ?>">
                                                    <?php echo htmlspecialchars($option['label']); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <!-- Regular text input (including dificultati and hobby) -->
                                    <!-- Regular text input -->
                                    <input type="<?php echo ($field_type === 'date') ? 'date' : (($field_type === 'email') ? 'email' : 'text'); ?>" 
                                           name="field_<?php echo $field_name; ?>" 
                                           value="<?php echo htmlspecialchars($default_value); ?>"
                                           <?php echo !$is_optional ? 'required' : ''; ?>
                                           placeholder="<?php echo $field_placeholder; ?>"
                                           class="contract-field"
                                           data-field="FIELD_<?php echo strtoupper($field_name); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($current_section != ''): ?>
                            </div></div> <!-- Close last section grid and section -->
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- CONTRACT PREVIEW -->
                <div class="section">
                    <h2>📄 Contract (Preview)</h2>
                    <div class="contract-preview" id="contractPreview">
                        <?php echo $contract['contract_content']; ?>
                    </div>
                    <p style="color: #666; font-size: 14px;"><em>Contractul se actualizează automat pe măsură ce completați datele.</em></p>
                </div>
                
                <!-- SIGNATURE -->
                <div class="signature-section">
                    <h3 style="text-align: center; margin-bottom: 20px;">✍️ Semnătură</h3>
                    <canvas id="signaturePad" class="signature-pad" width="600" height="200"></canvas>
                    
                    <input type="hidden" name="signature_data" id="signatureData">
                    <div class="button-group">
                        <button type="button" class="btn btn-clear" onclick="clearSignature()">🗑️ Șterge</button>
                        <button type="submit" class="btn btn-sign">✅ SEMNEAZĂ ȘI TRIMITE</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Live preview update
        const contractFields = document.querySelectorAll('.contract-field');
        const contractPreview = document.getElementById('contractPreview');
        
        // ✅ FIX: Salvează HTML-ul original pentru a evita bug-ul "doar prima literă vizibilă"
        const ORIGINAL_CONTRACT_HTML = contractPreview.innerHTML;
        
        // Preview update is now handled by auto-save listeners above
        // (auto-save listeners call both debouncedSave and updatePreview)
        contractFields.forEach(field => {
            field.addEventListener('input', updatePreview);
            field.addEventListener('change', updatePreview);
        });
        
        // Flag pentru a preveni loop infinit
        let isUpdatingPreview = false;
        
        function updatePreview() {
            // Previne loop infinit
            if (isUpdatingPreview) return;
            isUpdatingPreview = true;
            
            try {
                const preview = document.getElementById('contractPreview');
                if (!preview) return;
                
                // ✅ FIX: Folosește HTML-ul original, NU cel deja modificat!
                let content = ORIGINAL_CONTRACT_HTML;
                
                contractFields.forEach(field => {
                    const fieldName = field.dataset.field; // ex: "field_nume_mama"
                    let fieldValue = '';
                    
                    if (field.type === 'radio') {
                        if (field.checked) {
                            fieldValue = field.value;
                        }
                    } else {
                        fieldValue = field.value;
                    }
                    
                    if (fieldValue) {
                        // METODĂ NOUĂ: Găsește <span data-field="field_nume_mama">[NUME_MAMA]</span>
                        // și înlocuiește [NUME_MAMA] cu valoarea
                        
                        // Creează regex pentru a găsi span-ul cu data-field
                        // Pattern: <span data-field="field_nume_mama">[ORICE]</span>
                        const spanRegex = new RegExp(
                            '<span\\s+data-field="' + fieldName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '"[^>]*>\\[([^\]]+)\\]</span>',
                            'gi'
                        );
                        
                        // Înlocuiește cu: <span data-field="field_nume_mama">VALOAREA</span>
                        content = content.replace(spanRegex, function(match, placeholderName) {
                            return '<span data-field="' + fieldName + '">' + fieldValue + '</span>';
                        });
                        
                        // FALLBACK: Dacă nu găsește span cu data-field, caută placeholder simplu [FIELD_NAME]
                        // (pentru compatibilitate cu template-uri vechi)
                        const upperFieldName = fieldName.toUpperCase().replace('FIELD_', '');
                        const simpleRegex = new RegExp('\\[' + upperFieldName + '\\]', 'g');
                        content = content.replace(simpleRegex, fieldValue);
                    }
                });
                
                // Actualizează preview-ul
                contractPreview.innerHTML = content;
                
                // Special handling pentru câmpurile de program (checkmark-uri)
                // Găsește valoarea selectată pentru program
                const programAnexaRadios = document.querySelectorAll('input[name="field_optiune_program_anexa1"]:checked, input[name="field_optiune_program_anexa2"]:checked');
                if (programAnexaRadios.length > 0) {
                    const selectedProgram = programAnexaRadios[0].value; // 'scurt', 'mediu', 'lung'
                    updatePreviewWithProgram(selectedProgram);
                }
                
                contractPreview.innerHTML = content;
                
                // Sincronizare checkbox-uri GDPR
                syncGDPRCheckboxes();
                
            } finally {
                setTimeout(() => { isUpdatingPreview = false; }, 100);
            }
        }
        
        // ═══════════════════════════════════════════════════════════
        // Funcție pentru sincronizare checkbox-uri GDPR
        // ═══════════════════════════════════════════════════════════
        function syncGDPRCheckboxes() {
            const preview = document.getElementById('contractPreview');
            if (!preview) return;
            
            let previewHTML = preview.innerHTML;
            
            // ═══════════════════════════════════════════════════════════════
            // GDPR Processing Consent - Try 8.3 first (new templates), then 9.3 (old templates)
            // ═══════════════════════════════════════════════════════════════
            const gdpr93DA = document.querySelector('input[name="field_gdpr_processing_consent"][value="DA"]');
            const gdpr93NU = document.querySelector('input[name="field_gdpr_processing_consent"][value="NU"]');
            
            if (gdpr93DA && gdpr93NU) {
                const isDaChecked = gdpr93DA.checked;
                const isNuChecked = gdpr93NU.checked;
                
                // Try 8.3 first (new templates)
                let section93Start = previewHTML.indexOf('<strong>8.3</strong>');
                let sectionFound = false;
                
                if (section93Start !== -1) {
                    sectionFound = true;
                } else {
                    // Try 9.3 (old templates)
                    section93Start = previewHTML.indexOf('<strong>9.3.</strong>');
                    if (section93Start !== -1) {
                        sectionFound = true;
                    }
                }
                
                if (sectionFound) {
                    // Caută pattern-ul după 9.3
                    const searchAfter93 = previewHTML.substring(section93Start);
                    
                    // Pattern: DA ☐ (spații) NU ☐
                    const pattern93 = /(<strong>\s*)DA\s*[☐☑]\s*(&nbsp;)*\s*NU\s*[☐☑](\s*<\/strong>)/i;
                    const match93 = searchAfter93.match(pattern93);
                    
                    if (match93) {
                        const daSymbol = isDaChecked ? '☑' : '☐';
                        const nuSymbol = isNuChecked ? '☑' : '☐';
                        
                        const replacement93 = match93[1] + 'DA ' + daSymbol + ' &nbsp;&nbsp;&nbsp;&nbsp; NU ' + nuSymbol + match93[3];
                        
                        // Înlocuiește în substring
                        const newSearchAfter93 = searchAfter93.replace(pattern93, replacement93);
                        
                        // Reconstruiește HTML-ul
                        previewHTML = previewHTML.substring(0, section93Start) + newSearchAfter93;
                    }
                }
            }
            
            // ═══════════════════════════════════════════════════════════════
            // GDPR Photo/Video Consent - Try 8.4 first (new templates), then 9.4 (old templates)
            // ═══════════════════════════════════════════════════════════════
            const gdpr94DA = document.querySelector('input[name="field_gdpr_photo_video_consent"][value="DA"]');
            const gdpr94NU = document.querySelector('input[name="field_gdpr_photo_video_consent"][value="NU"]');
            
            if (gdpr94DA && gdpr94NU) {
                const isDaChecked = gdpr94DA.checked;
                const isNuChecked = gdpr94NU.checked;
                
                // Try 8.4 first (new templates)
                let section94Start = previewHTML.indexOf('<strong>8.4</strong>');
                let sectionFound = false;
                
                if (section94Start !== -1) {
                    sectionFound = true;
                } else {
                    // Try 9.4 (old templates)
                    section94Start = previewHTML.indexOf('<strong>9.4.</strong>');
                    if (section94Start !== -1) {
                        sectionFound = true;
                    }
                }
                
                if (sectionFound) {
                    const searchAfter94 = previewHTML.substring(section94Start);
                    
                    const pattern94 = /(<strong>\s*)DA\s*[☐☑]\s*(&nbsp;)*\s*NU\s*[☐☑](\s*<\/strong>)/i;
                    const match94 = searchAfter94.match(pattern94);
                    
                    if (match94) {
                        const daSymbol = isDaChecked ? '☑' : '☐';
                        const nuSymbol = isNuChecked ? '☑' : '☐';
                        
                        const replacement94 = match94[1] + 'DA ' + daSymbol + ' &nbsp;&nbsp;&nbsp;&nbsp; NU ' + nuSymbol + match94[3];
                        
                        const newSearchAfter94 = searchAfter94.replace(pattern94, replacement94);
                        previewHTML = previewHTML.substring(0, section94Start) + newSearchAfter94;
                    }
                }
            }
            
            
            // ═══════════════════════════════════════════════════════════════
            // GRUPA/CLASA - Template 7 (checkbox sync)
            // ═══════════════════════════════════════════════════════════════
            const grupaMica = document.querySelector('input[name="field_grupa_clasa"][value="mica"]');
            const grupaMijlocie = document.querySelector('input[name="field_grupa_clasa"][value="mijlocie"]');
            const grupaMare = document.querySelector('input[name="field_grupa_clasa"][value="mare"]');
            
            if (grupaMica || grupaMijlocie || grupaMare) {
                // Reset all checkboxes to unchecked first
                previewHTML = previewHTML.replace(/☑(\s*<strong>Grupa mica: 3 - 4 ani<\/strong>)/gi, '☐$1');
                previewHTML = previewHTML.replace(/☑(\s*<strong>Grupa mijlocie: 4 - 5 ani<\/strong>)/gi, '☐$1');
                previewHTML = previewHTML.replace(/☑(\s*<strong>Grupa mare: 5 - 6 ani<\/strong>)/gi, '☐$1');
                
                // Set the checked one
                if (grupaMica && grupaMica.checked) {
                    previewHTML = previewHTML.replace(/☐(\s*<strong>Grupa mica: 3 - 4 ani<\/strong>)/i, '☑$1');
                    console.log('Preview: Grupa mica checked');
                } else if (grupaMijlocie && grupaMijlocie.checked) {
                    previewHTML = previewHTML.replace(/☐(\s*<strong>Grupa mijlocie: 4 - 5 ani<\/strong>)/i, '☑$1');
                    console.log('Preview: Grupa mijlocie checked');
                } else if (grupaMare && grupaMare.checked) {
                    previewHTML = previewHTML.replace(/☐(\s*<strong>Grupa mare: 5 - 6 ani<\/strong>)/i, '☑$1');
                    console.log('Preview: Grupa mare checked');
                }
            }
            
            // ═══════════════════════════════════════════════════════════════
            // ANEXA 1 & 2 - OPȚIUNE PROGRAM (checkbox sync)
            // ═══════════════════════════════════════════════════════════════
            
            // Anexa 1 - Program options
            const anexa1Scurt = document.querySelector('input[name="field_optiune_program_anexa1"][value="scurt"]');
            const anexa1Mediu = document.querySelector('input[name="field_optiune_program_anexa1"][value="mediu"]');
            const anexa1Lung = document.querySelector('input[name="field_optiune_program_anexa1"][value="lung"]');
            
            // Anexa 2 - Program options
            const anexa2Scurt = document.querySelector('input[name="field_optiune_program_anexa2"][value="scurt"]');
            const anexa2Mediu = document.querySelector('input[name="field_optiune_program_anexa2"][value="mediu"]');
            const anexa2Lung = document.querySelector('input[name="field_optiune_program_anexa2"][value="lung"]');
            
            // Sincronizează AMBELE ANEXE (1 și 2) cu ACEEAȘI valoare
            // Radio buttons sunt sincronizați, deci preview-ul trebuie și el sincronizat
            if (anexa1Scurt || anexa1Mediu || anexa1Lung || anexa2Scurt || anexa2Mediu || anexa2Lung) {
                // Determină ce program e selectat (verifică ambele anexe)
                let selectedProgram = null;
                
                // 🔍 DEBUG: Log starea EXACTĂ a tuturor radio buttons
                console.log('🔍 updatePreview() - Starea radio buttons:', {
                    'Anexa1 scurt': anexa1Scurt?.checked,
                    'Anexa1 mediu': anexa1Mediu?.checked,
                    'Anexa1 lung': anexa1Lung?.checked,
                    'Anexa2 scurt': anexa2Scurt?.checked,
                    'Anexa2 mediu': anexa2Mediu?.checked,
                    'Anexa2 lung': anexa2Lung?.checked
                });
                
                // ⚡ FIX v10: Verifică AMBELE anexe simultan - prioritizează Anexa1
                // Problema: if-else secvențial găsea primul true și se oprea
                // Soluție: Verifică ce e checked și determină corect programul
                
                // Prioritate 1: Verifică dacă AMBELE anexe sunt sincronizate corect
                if ((anexa1Scurt && anexa1Scurt.checked) && (anexa2Scurt && anexa2Scurt.checked)) {
                    selectedProgram = 'scurt';
                } else if ((anexa1Mediu && anexa1Mediu.checked) && (anexa2Mediu && anexa2Mediu.checked)) {
                    selectedProgram = 'mediu';
                } else if ((anexa1Lung && anexa1Lung.checked) && (anexa2Lung && anexa2Lung.checked)) {
                    selectedProgram = 'lung';
                }
                // Prioritate 2: Dacă nu sunt sincronizate, prioritizează Anexa1
                else if (anexa1Scurt && anexa1Scurt.checked) {
                    selectedProgram = 'scurt';
                } else if (anexa1Mediu && anexa1Mediu.checked) {
                    selectedProgram = 'mediu';
                } else if (anexa1Lung && anexa1Lung.checked) {
                    selectedProgram = 'lung';
                }
                // Prioritate 3: Dacă Anexa1 nu are nimic, folosește Anexa2
                else if (anexa2Scurt && anexa2Scurt.checked) {
                    selectedProgram = 'scurt';
                } else if (anexa2Mediu && anexa2Mediu.checked) {
                    selectedProgram = 'mediu';
                } else if (anexa2Lung && anexa2Lung.checked) {
                    selectedProgram = 'lung';
                }
                
                console.log('✅ selectedProgram determinat:', selectedProgram);
                
                // Resetează TOATE checkbox-urile (ANEXA 1 + ANEXA 2) la ☐
                // Folosește regex pentru a găsi pattern-ul indiferent de spații
                previewHTML = previewHTML.replace(/☑\s*<strong>Program scurt:<\/strong>/g, '☐ <strong>Program scurt:</strong>');
                previewHTML = previewHTML.replace(/☑\s*<strong>Program mediu:<\/strong>/g, '☐ <strong>Program mediu:</strong>');
                previewHTML = previewHTML.replace(/☑\s*<strong>Program lung:<\/strong>/g, '☐ <strong>Program lung:</strong>');
                
                previewHTML = previewHTML.replace(/<p>☑\s*<strong>Program scurt:<\/strong>/g, '<p>☐ <strong>Program scurt:</strong>');
                previewHTML = previewHTML.replace(/<p>☑\s*<strong>Program mediu:<\/strong>/g, '<p>☐ <strong>Program mediu:</strong>');
                previewHTML = previewHTML.replace(/<p>☑\s*<strong>Program lung:<\/strong>/g, '<p>☐ <strong>Program lung:</strong>');
                
                // Bifează DIRECT cu regex pentru TOATE aparițiile
                if (selectedProgram) {
                    // Folosește regex cu flag 'g' pentru a înlocui TOATE aparițiile
                    // (nu doar prima apariție cum face string.replace())
                    
                    if (selectedProgram === 'scurt') {
                        // Bifează TOATE aparițiile (atât ANEXA 1 cât și ANEXA 2)
                        previewHTML = previewHTML.replace(/☐\s*<strong>Program scurt:<\/strong>/g, '☑ <strong>Program scurt:</strong>');
                        
                    } else if (selectedProgram === 'mediu') {
                        previewHTML = previewHTML.replace(/☐\s*<strong>Program mediu:<\/strong>/g, '☑ <strong>Program mediu:</strong>');
                        
                    } else if (selectedProgram === 'lung') {
                        previewHTML = previewHTML.replace(/☐\s*<strong>Program lung:<\/strong>/g, '☑ <strong>Program lung:</strong>');
                    }
                }
            }
            
            preview.innerHTML = previewHTML;
        }
        
        // Signature pad
        const canvas = document.getElementById('signaturePad');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        let hasSignature = false;
        
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);
        
        canvas.addEventListener('touchstart', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        });
        
        canvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = canvas.getBoundingClientRect();
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        });
        
        canvas.addEventListener('touchend', function(e) {
            e.preventDefault();
            const mouseEvent = new MouseEvent('mouseup', {});
            canvas.dispatchEvent(mouseEvent);
        });
        
        function startDrawing(e) {
            isDrawing = true;
            hasSignature = true;
            const rect = canvas.getBoundingClientRect();
            ctx.beginPath();
            ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
        }
        
        function draw(e) {
            if (!isDrawing) return;
            
            const rect = canvas.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.stroke();
        }
        
        function stopDrawing() {
            if (isDrawing) {
                isDrawing = false;
                document.getElementById('signatureData').value = canvas.toDataURL();
            }
        }
        
        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            document.getElementById('signatureData').value = '';
            hasSignature = false;
        }
        
        // Form validation
        document.getElementById('mainForm').addEventListener('submit', function(e) {
            if (!hasSignature) {
                e.preventDefault();
                alert('Vă rugăm să semnați contractul!');
                return false;
            }
            
            // Clear saved data on successful submit
            localStorage.removeItem('contract_form_data_fallback');
            
            // Mutual exclusion is handled automatically by JavaScript (see below)
        });
        
        // ═══════════════════════════════════════════════════════════════
        // AUTO-SAVE FUNCTIONALITY - Save form data to localStorage
        // ═══════════════════════════════════════════════════════════════
        
        const STORAGE_KEY = 'contract_form_data_fallback';
        let saveTimeout;
        
        // Save form data to localStorage
        function saveFormData() {
            const formData = {};
            
            // Save all contract fields (text, date, textarea, etc.)
            contractFields.forEach(field => {
                const fieldName = field.name;
                
                if (field.type === 'radio') {
                    if (field.checked) {
                        formData[fieldName] = field.value;
                    }
                } else if (field.type === 'checkbox') {
                    formData[fieldName] = field.checked;
                } else {
                    formData[fieldName] = field.value;
                }
            });
            
            // Save checkbox states for "Fără date mamă/tată"
            const noMamaCheckbox = document.getElementById('no_mama_data');
            const noTataCheckbox = document.getElementById('no_tata_data');
            if (noMamaCheckbox) formData['no_mama_data'] = noMamaCheckbox.checked;
            if (noTataCheckbox) formData['no_tata_data'] = noTataCheckbox.checked;
            
            // Save to localStorage
            localStorage.setItem(STORAGE_KEY, JSON.stringify(formData));
            
            // Update last save time
            const now = new Date();
            const timeStr = now.toLocaleTimeString('ro-RO', { hour: '2-digit', minute: '2-digit' });
            const lastSaveEl = document.getElementById('lastSaveTime');
            if (lastSaveEl) {
                lastSaveEl.textContent = `Ultima salvare: ${timeStr}`;
            }
        }
        
        // Debounced save (wait 500ms after last input)
        function debouncedSave() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveFormData, 500);
        }
        
        // Restore form data from localStorage
        function restoreFormData() {
            const savedData = localStorage.getItem(STORAGE_KEY);
            if (!savedData) return;
            
            try {
                const formData = JSON.parse(savedData);
                let restoredCount = 0;
                
                // Restore all fields
                contractFields.forEach(field => {
                    const fieldName = field.name;
                    if (formData.hasOwnProperty(fieldName)) {
                        if (field.type === 'radio') {
                            if (field.value === formData[fieldName]) {
                                field.checked = true;
                                restoredCount++;
                            }
                        } else if (field.type === 'checkbox') {
                            field.checked = formData[fieldName];
                            restoredCount++;
                        } else {
                            field.value = formData[fieldName];
                            if (formData[fieldName]) restoredCount++;
                        }
                    }
                });
                
                // Restore "Fără date mamă/tată" checkboxes
                const noMamaCheckbox = document.getElementById('no_mama_data');
                const noTataCheckbox = document.getElementById('no_tata_data');
                if (noMamaCheckbox && formData['no_mama_data']) {
                    noMamaCheckbox.checked = true;
                    noMamaCheckbox.dispatchEvent(new Event('change'));
                }
                if (noTataCheckbox && formData['no_tata_data']) {
                    noTataCheckbox.checked = true;
                    noTataCheckbox.dispatchEvent(new Event('change'));
                }
                
                // Update preview after restore
                updatePreview();
                
                // Show notification
                if (restoredCount > 0) {
                    const lastSaveEl = document.getElementById('lastSaveTime');
                    if (lastSaveEl) {
                        lastSaveEl.textContent = `Restaurate ${restoredCount} câmpuri salvate anterior`;
                        lastSaveEl.style.color = '#2e7d32';
                        lastSaveEl.style.fontWeight = 'bold';
                    }
                }
            } catch (e) {
                console.error('Error restoring form data:', e);
            }
        }
        
        // Clear saved data
        document.getElementById('clearSavedData').addEventListener('click', function() {
            if (confirm('⚠️ Ești sigur că vrei să ștergi toate datele salvate?\n\nAceastă acțiune nu poate fi anulată!')) {
                localStorage.removeItem(STORAGE_KEY);
                
                // Clear all form fields
                contractFields.forEach(field => {
                    if (field.type === 'radio' || field.type === 'checkbox') {
                        field.checked = false;
                    } else {
                        field.value = '';
                    }
                });
                
                // Uncheck "Fără date" checkboxes
                const noMamaCheckbox = document.getElementById('no_mama_data');
                const noTataCheckbox = document.getElementById('no_tata_data');
                if (noMamaCheckbox) {
                    noMamaCheckbox.checked = false;
                    noMamaCheckbox.dispatchEvent(new Event('change'));
                }
                if (noTataCheckbox) {
                    noTataCheckbox.checked = false;
                    noTataCheckbox.dispatchEvent(new Event('change'));
                }
                
                // Update preview
                updatePreview();
                
                // Show notification
                const lastSaveEl = document.getElementById('lastSaveTime');
                if (lastSaveEl) {
                    lastSaveEl.textContent = 'Toate datele au fost șterse!';
                    lastSaveEl.style.color = '#d32f2f';
                    lastSaveEl.style.fontWeight = 'bold';
                }
                
                alert('✅ Datele salvate au fost șterse cu succes!');
            }
        });
        
        // Attach auto-save to all form fields
        contractFields.forEach(field => {
            field.addEventListener('input', debouncedSave);
            field.addEventListener('change', debouncedSave);
        });
        
        // Also save when "Fără date" checkboxes change
        const noMamaCheckbox = document.getElementById('no_mama_data');
        const noTataCheckbox = document.getElementById('no_tata_data');
        if (noMamaCheckbox) noMamaCheckbox.addEventListener('change', debouncedSave);
        if (noTataCheckbox) noTataCheckbox.addEventListener('change', debouncedSave);
        
        // Restore data on page load
        restoreFormData();
        
        // ═══════════════════════════════════════════════════════════════
        // SINCRONIZARE ANEXA 1 ↔ ANEXA 2 (Program Options)
        // ═══════════════════════════════════════════════════════════════
        // Când selectezi "Program scurt" în Anexa 1 → se selectează automat și în Anexa 2
        
        // ⚡ FIX v11: FLAG GLOBAL pentru a preveni loop infinit
        // IMPORTANT: Trebuie să fie ÎNAINTE de funcție pentru a persista între events!
        let isSyncingProgram = false;
        
        
        // ⚡ FIX v12: Funcție helper pentru a actualiza preview-ul cu program specificat
        function updatePreviewWithProgram(selectedProgram) {
            const preview = document.getElementById('contractPreview');
            if (!preview) return;
            
            let previewHTML = preview.innerHTML;
            
            console.log('📄 updatePreviewWithProgram apelat cu:', selectedProgram);
            
            // Resetează TOATE checkbox-urile la ☐
            previewHTML = previewHTML.replace(/☑\s*<strong>Program scurt:<\/strong>/g, '☐ <strong>Program scurt:</strong>');
            previewHTML = previewHTML.replace(/☑\s*<strong>Program mediu:<\/strong>/g, '☐ <strong>Program mediu:</strong>');
            previewHTML = previewHTML.replace(/☑\s*<strong>Program lung:<\/strong>/g, '☐ <strong>Program lung:</strong>');
            
            previewHTML = previewHTML.replace(/<p>☑\s*<strong>Program scurt:<\/strong>/g, '<p>☐ <strong>Program scurt:</strong>');
            previewHTML = previewHTML.replace(/<p>☑\s*<strong>Program mediu:<\/strong>/g, '<p>☐ <strong>Program mediu:</strong>');
            previewHTML = previewHTML.replace(/<p>☑\s*<strong>Program lung:<\/strong>/g, '<p>☐ <strong>Program lung:</strong>');
            
            // Bifează programul selectat
            if (selectedProgram === 'scurt') {
                previewHTML = previewHTML.replace(/☐\s*<strong>Program scurt:<\/strong>/g, '☑ <strong>Program scurt:</strong>');
            } else if (selectedProgram === 'mediu') {
                previewHTML = previewHTML.replace(/☐\s*<strong>Program mediu:<\/strong>/g, '☑ <strong>Program mediu:</strong>');
            } else if (selectedProgram === 'lung') {
                previewHTML = previewHTML.replace(/☐\s*<strong>Program lung:<\/strong>/g, '☑ <strong>Program lung:</strong>');
            }
            
            preview.innerHTML = previewHTML;
            console.log('✅ Preview actualizat cu program:', selectedProgram);
        }

        function syncProgramOptions() {
            const anexa1Radios = document.querySelectorAll('input[name="field_optiune_program_anexa1"]');
            const anexa2Radios = document.querySelectorAll('input[name="field_optiune_program_anexa2"]');
            const grupaClasaRadios = document.querySelectorAll('input[name="field_grupa_clasa"]');
            
            // Anexa 1 → Anexa 2
            anexa1Radios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (isSyncingProgram) return; // STOP loop!
                    
                    if (this.checked) {
                        isSyncingProgram = true;
                        const selectedValue = this.value; // 'scurt', 'mediu', sau 'lung'
                        
                        console.log('🔄 Anexa1 click:', selectedValue);
                        
                        // Găsește și selectează același tip în Anexa 2
                        anexa2Radios.forEach(a2radio => {
                            if (a2radio.value === selectedValue) {
                                a2radio.checked = true;
                                // NU trigger change event - previne loop
                            }
                        });
                        
                        // ⚡ FIX v12: Apelează updatePreview() IMEDIAT cu valoarea corectă
                        // NU mai aștepta setTimeout - folosește valoarea din event direct!
                        updatePreviewWithProgram(selectedValue);
                        
                        // Eliberează flag-ul după un delay mic
                        setTimeout(() => {
                            isSyncingProgram = false;
                        }, 10);
                    }
                });
            });
            
            // Anexa 2 → Anexa 1 (sincronizare bidirecțională)
            anexa2Radios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (isSyncingProgram) return; // STOP loop!
                    
                    if (this.checked) {
                        isSyncingProgram = true;
                        const selectedValue = this.value;
                        
                        console.log('🔄 Anexa2 click:', selectedValue);
                        
                        anexa1Radios.forEach(a1radio => {
                            if (a1radio.value === selectedValue) {
                                a1radio.checked = true;
                                // NU trigger change event - previne loop
                            }
                        });
                        
                        // ⚡ FIX v12: Apelează updatePreview() IMEDIAT cu valoarea corectă
                        updatePreviewWithProgram(selectedValue);
                        
                        // Eliberează flag-ul după un delay mic
                        setTimeout(() => {
                            isSyncingProgram = false;
                        }, 10);
                    }
                });
            });
        
        
            // Event listeners for grupa_clasa radios
            grupaClasaRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    console.log('grupa_clasa changed:', this.value);
                    updatePreview();
                });
            });
        }
        
        // Activează sincronizarea după ce pagina s-a încărcat
        window.addEventListener('DOMContentLoaded', syncProgramOptions);
        
        // ========================================
        // Auto-fill MAMĂ/TATĂ fields with "-" when checkbox is checked
        // ========================================
        
        function setupNoDataCheckbox(checkboxId, fieldPatterns) {
            const checkbox = document.getElementById(checkboxId);
            if (!checkbox) return;
            
            checkbox.addEventListener('change', function() {
                // Find all input/textarea fields matching the patterns
                const allFields = [];
                fieldPatterns.forEach(pattern => {
                    const fields = document.querySelectorAll(`input[name="${pattern}"], textarea[name="${pattern}"]`);
                    fields.forEach(f => allFields.push(f));
                });
                
                if (allFields.length === 0) {
                    console.warn(`No fields found for checkbox ${checkboxId}. Expected patterns:`, fieldPatterns);
                    return;
                }
                
                allFields.forEach(field => {
                    if (this.checked) {
                        // Checkbox is checked → fill with "-" and disable
                        field.value = '-';
                        field.disabled = true;
                        field.style.backgroundColor = '#f5f5f5';
                        field.style.color = '#999';
                        field.style.cursor = 'not-allowed';
                    } else {
                        // Checkbox is unchecked → clear and enable
                        if (field.value === '-') {
                            field.value = '';
                        }
                        field.disabled = false;
                        field.style.backgroundColor = '';
                        field.style.color = '';
                        field.style.cursor = '';
                    }
                });
                
                // Visual feedback
                const parentName = checkboxId === 'no_mama_data' ? 'mamă' : 'tată';
                if (this.checked) {
                    console.log(`✅ Toate câmpurile pentru ${parentName} au fost completate cu "-" și dezactivate.`);
                } else {
                    console.log(`❌ Câmpurile pentru ${parentName} au fost golite și reactivate.`);
                }
            });
        }
        
        // Setup checkboxes after page load
        window.addEventListener('DOMContentLoaded', function() {
            const mamaFields = [
                'field_nume_mama', 'field_cetatenie_mama', 'field_nationalitate_mama', 'field_adresa_mama',
                'field_telefon_mama', 'field_email_mama', 'field_loc_munca_mama', 'field_functie_mama',
                'field_tip_act_mama', 'field_serie_act_mama', 'field_numar_act_mama', 
                'field_data_emitere_mama', 'field_emis_de_mama', 'field_cnp_mama'
            ];
            
            const tataFields = [
                'field_nume_tata', 'field_cetatenie_tata', 'field_nationalitate_tata', 'field_adresa_tata',
                'field_telefon_tata', 'field_email_tata', 'field_loc_munca_tata', 'field_functie_tata',
                'field_tip_act_tata', 'field_serie_act_tata', 'field_numar_act_tata',
                'field_data_emitere_tata', 'field_emis_de_tata', 'field_cnp_tata'
            ];
            
            setupNoDataCheckbox('no_mama_data', mamaFields);
            setupNoDataCheckbox('no_tata_data', tataFields);
            
            // ========================================
            // Mutual exclusion: Debifă automat celălalt checkbox
            // ========================================
            const mamaCheckbox = document.getElementById('no_mama_data');
            const tataCheckbox = document.getElementById('no_tata_data');
            
            if (mamaCheckbox && tataCheckbox) {
                mamaCheckbox.addEventListener('change', function() {
                    if (this.checked && tataCheckbox.checked) {
                        // Dacă bifez MAMĂ și TATĂ e deja bifat → debifă TATĂ
                        tataCheckbox.checked = false;
                        tataCheckbox.dispatchEvent(new Event('change'));
                    }
                });
                
                tataCheckbox.addEventListener('change', function() {
                    if (this.checked && mamaCheckbox.checked) {
                        // Dacă bifez TATĂ și MAMĂ e deja bifat → debifă MAMĂ
                        mamaCheckbox.checked = false;
                        mamaCheckbox.dispatchEvent(new Event('change'));
                    }
                });
            }
            
            // ═══════════════════════════════════════════════════════════
            // Event listeners pentru checkbox-uri GDPR (FIX NOU)
            // ═══════════════════════════════════════════════════════════
            const gdprCheckboxes = document.querySelectorAll('input[type="radio"][name*="consimtamant"]');
            
            gdprCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updatePreview();
                });
            });
            
            // Trigger inițial
            setTimeout(() => {
                updatePreview();
            }, 500);
        });
    </script>
</body>
</html>
