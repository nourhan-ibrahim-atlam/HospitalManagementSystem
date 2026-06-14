<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalHistorySeeder extends Seeder
{
    // ─── Batch collectors for bulk inserts ───────────────────────────────────────
    private array $vitalSignsBatch    = [];
    private array $diagnosesBatch     = [];
    private array $allergiesBatch     = [];
    private array $bloodParamsBatch   = [];
    private array $surgeriesBatch     = [];
    private array $immunizationsBatch = [];
    private int   $historyCount       = 0;

    // ─── Realistic condition catalogue ───────────────────────────────────────────
    private array $conditions = [
        [
            'chief_complaint'    => 'Chest pain and shortness of breath',
            'diagnosis'          => 'Acute Coronary Syndrome',
            'icd_code'           => 'I24.9',
            'present_illness'    => 'Patient reports sudden onset of crushing chest pain radiating to the left arm and jaw, associated with diaphoresis and nausea for the past 2 hours.',
            'treatment_plan'     => 'Immediate ECG, troponin levels, aspirin 325mg, nitroglycerin SL. Cardiology consultation and possible PCI.',
            'medications'        => 'Aspirin 325mg daily, Metoprolol 25mg BD, Atorvastatin 40mg OD, Clopidogrel 75mg OD',
            'visit_type'         => 'emergency',
            'status'             => 'active',
            'allergen'           => 'Aspirin',
            'reaction'           => 'Hypersensitivity rash',
            'allergy_severity'   => 'moderate',
        ],
        [
            'chief_complaint'    => 'Uncontrolled blood sugar levels',
            'diagnosis'          => 'Type 2 Diabetes Mellitus, Uncontrolled',
            'icd_code'           => 'E11.9',
            'present_illness'    => 'Patient has known Type 2 Diabetes for 5 years. HbA1c now 10.2%. Reports polydipsia, polyuria, and recent 5kg weight loss over 2 months.',
            'treatment_plan'     => 'Optimize glycemic control. Add insulin basal. Diabetic diet counseling. Referral to endocrinology.',
            'medications'        => 'Metformin 1000mg BD, Glibenclamide 5mg OD, Insulin Glargine 20 units HS',
            'visit_type'         => 'follow_up',
            'status'             => 'active',
            'allergen'           => null,
            'reaction'           => null,
            'allergy_severity'   => null,
        ],
        [
            'chief_complaint'    => 'Persistent high blood pressure',
            'diagnosis'          => 'Essential Hypertension Stage 2',
            'icd_code'           => 'I10',
            'present_illness'    => 'BP readings at home consistently above 160/100. Patient denies adherence to low-sodium diet. Family history of hypertension.',
            'treatment_plan'     => 'Add amlodipine 5mg. DASH diet counseling. Home BP monitoring. Follow up in 2 weeks.',
            'medications'        => 'Amlodipine 5mg OD, Lisinopril 10mg OD, Hydrochlorothiazide 12.5mg OD',
            'visit_type'         => 'follow_up',
            'status'             => 'active',
            'allergen'           => 'Penicillin',
            'reaction'           => 'Anaphylaxis',
            'allergy_severity'   => 'life_threatening',
        ],
        [
            'chief_complaint'    => 'Wheezing and difficulty breathing',
            'diagnosis'          => 'Acute Asthma Exacerbation',
            'icd_code'           => 'J45.901',
            'present_illness'    => 'Known asthmatic presenting with acute exacerbation triggered by dust exposure at construction site. O2 sat 91% on room air.',
            'treatment_plan'     => 'Salbutamol nebulization q20min x3, IV methylprednisolone, oxygen therapy. Discharge on prednisolone 40mg x5 days.',
            'medications'        => 'Salbutamol inhaler PRN, Fluticasone/Salmeterol 250/50 BD, Montelukast 10mg OD',
            'visit_type'         => 'emergency',
            'status'             => 'resolved',
            'allergen'           => 'Dust mites',
            'reaction'           => 'Severe bronchospasm',
            'allergy_severity'   => 'severe',
        ],
        [
            'chief_complaint'    => 'Joint pain and swelling in both knees',
            'diagnosis'          => 'Osteoarthritis, Bilateral Knees',
            'icd_code'           => 'M17.0',
            'present_illness'    => 'Elderly patient with bilateral knee pain worsening over 3 years. Pain score 7/10 at rest, 9/10 on walking. Morning stiffness lasting 30 minutes.',
            'treatment_plan'     => 'Physiotherapy referral, weight loss program, NSAIDs for pain, knee brace. Consider orthopedic consult for joint replacement.',
            'medications'        => 'Diclofenac 75mg BD with food, Paracetamol 500mg TDS, Glucosamine 1500mg OD',
            'visit_type'         => 'consultation',
            'status'             => 'active',
            'allergen'           => null,
            'reaction'           => null,
            'allergy_severity'   => null,
        ],
        [
            'chief_complaint'    => 'High fever and productive cough',
            'diagnosis'          => 'Community-Acquired Pneumonia',
            'icd_code'           => 'J18.9',
            'present_illness'    => 'Fever 39.5°C, productive cough with yellowish sputum, right-sided pleuritic chest pain x3 days. CXR shows right lower lobe consolidation.',
            'treatment_plan'     => 'Amoxicillin-clavulanate 875mg BD x7 days. Rest, adequate hydration. Follow-up CXR in 4 weeks.',
            'medications'        => 'Amoxicillin-Clavulanate 875mg BD, Azithromycin 500mg OD x5 days, Paracetamol 500mg PRN',
            'visit_type'         => 'initial',
            'status'             => 'resolved',
            'allergen'           => null,
            'reaction'           => null,
            'allergy_severity'   => null,
        ],
        [
            'chief_complaint'    => 'Abdominal pain and nausea',
            'diagnosis'          => 'Gastroesophageal Reflux Disease (GERD)',
            'icd_code'           => 'K21.0',
            'present_illness'    => 'Burning epigastric pain after meals, acid regurgitation, nocturnal symptoms waking patient. Symptoms x6 months.',
            'treatment_plan'     => 'PPI therapy for 8 weeks. Avoid triggers. Elevate head of bed. Weight reduction.',
            'medications'        => 'Omeprazole 40mg OD before breakfast, Antacid liquid PRN, Domperidone 10mg before meals',
            'visit_type'         => 'initial',
            'status'             => 'active',
            'allergen'           => null,
            'reaction'           => null,
            'allergy_severity'   => null,
        ],
        [
            'chief_complaint'    => 'Persistent low mood and loss of interest',
            'diagnosis'          => 'Major Depressive Disorder',
            'icd_code'           => 'F32.9',
            'present_illness'    => 'Patient reports 4-month history of depressed mood, anhedonia, insomnia, reduced appetite, poor concentration. PHQ-9 score 19 (severe).',
            'treatment_plan'     => 'Sertraline 50mg OD, increase to 100mg after 2 weeks. Weekly psychotherapy sessions. Safety plan established.',
            'medications'        => 'Sertraline 100mg OD, Mirtazapine 15mg HS for sleep',
            'visit_type'         => 'initial',
            'status'             => 'active',
            'allergen'           => null,
            'reaction'           => null,
            'allergy_severity'   => null,
        ],
        [
            'chief_complaint'    => 'Fatigue and weight gain',
            'diagnosis'          => 'Hypothyroidism',
            'icd_code'           => 'E03.9',
            'present_illness'    => 'TSH 12.4 mIU/L, T4 low. Patient reports fatigue, cold intolerance, constipation, weight gain 8kg in 6 months, dry skin.',
            'treatment_plan'     => 'Levothyroxine replacement, start 50mcg OD increasing as tolerated. Recheck TFTs in 6 weeks.',
            'medications'        => 'Levothyroxine 100mcg OD (empty stomach)',
            'visit_type'         => 'initial',
            'status'             => 'active',
            'allergen'           => null,
            'reaction'           => null,
            'allergy_severity'   => null,
        ],
        [
            'chief_complaint'    => 'Back pain radiating to right leg',
            'diagnosis'          => 'Lumbar Disc Herniation with Radiculopathy',
            'icd_code'           => 'M51.1',
            'present_illness'    => 'Sudden onset low back pain after lifting heavy object. Pain radiates down right leg to foot (L4/L5 distribution). Positive SLR at 45°.',
            'treatment_plan'     => 'MRI lumbar spine, NSAIDs, physiotherapy. Epidural steroid injection if no improvement in 6 weeks.',
            'medications'        => 'Ibuprofen 600mg TDS with food, Pregabalin 75mg BD, Cyclobenzaprine 5mg HS',
            'visit_type'         => 'initial',
            'status'             => 'active',
            'allergen'           => null,
            'reaction'           => null,
            'allergy_severity'   => null,
        ],
    ];

    // ─── Surgery catalogue ────────────────────────────────────────────────────────
    private array $surgeryCatalogue = [
        ['surgery_name' => 'Appendectomy',                          'hospital' => 'General Hospital',       'surgeon_name' => 'Dr. Ahmed Hassan',       'reason' => 'Acute appendicitis'],
        ['surgery_name' => 'Cholecystectomy',                       'hospital' => 'City Medical Center',    'surgeon_name' => 'Dr. Sara Khalil',         'reason' => 'Symptomatic gallstones'],
        ['surgery_name' => 'Coronary Artery Bypass Grafting (CABG)','hospital' => 'Cardiac Institute',      'surgeon_name' => 'Dr. Mohammed Al-Rashid',  'reason' => 'Severe coronary artery disease'],
        ['surgery_name' => 'Total Knee Replacement',                'hospital' => 'Orthopedic Hospital',    'surgeon_name' => 'Dr. Laila Nasser',         'reason' => 'End-stage osteoarthritis'],
        ['surgery_name' => 'Hernia Repair',                         'hospital' => 'General Hospital',       'surgeon_name' => 'Dr. Youssef Mahmoud',      'reason' => 'Inguinal hernia'],
        ['surgery_name' => 'Caesarean Section',                     'hospital' => 'Maternity Hospital',     'surgeon_name' => 'Dr. Fatima Al-Zahra',      'reason' => 'Failure to progress in labor'],
        ['surgery_name' => 'Thyroidectomy',                         'hospital' => 'ENT Surgical Center',    'surgeon_name' => 'Dr. Khaled Ibrahim',       'reason' => 'Thyroid nodule with suspicious biopsy'],
        ['surgery_name' => 'Cataract Surgery',                      'hospital' => 'Eye Care Hospital',      'surgeon_name' => 'Dr. Amina Saleh',          'reason' => 'Bilateral cataracts affecting vision'],
        ['surgery_name' => 'Splenectomy',                           'hospital' => 'University Hospital',    'surgeon_name' => 'Dr. Omar Farouk',          'reason' => 'Traumatic splenic rupture'],
        ['surgery_name' => 'Hip Replacement',                       'hospital' => 'Orthopedic Hospital',    'surgeon_name' => 'Dr. Laila Nasser',         'reason' => 'Avascular necrosis of femoral head'],
    ];

    // ─── Vaccine catalogue ────────────────────────────────────────────────────────
    private array $vaccineCatalogue = [
        ['vaccine_name' => 'COVID-19 Vaccine (Pfizer-BioNTech)', 'manufacturer' => 'Pfizer',            'next_dose_months' => 6],
        ['vaccine_name' => 'Influenza Vaccine',                  'manufacturer' => 'Sanofi Pasteur',    'next_dose_months' => 12],
        ['vaccine_name' => 'Hepatitis B Vaccine',                'manufacturer' => 'GlaxoSmithKline',   'next_dose_months' => null],
        ['vaccine_name' => 'MMR Vaccine',                        'manufacturer' => 'Merck',             'next_dose_months' => null],
        ['vaccine_name' => 'Tetanus-Diphtheria (Td) Booster',   'manufacturer' => 'Sanofi Pasteur',    'next_dose_months' => 120],
        ['vaccine_name' => 'Pneumococcal Vaccine (PPSV23)',      'manufacturer' => 'Merck',             'next_dose_months' => 60],
        ['vaccine_name' => 'Hepatitis A Vaccine',                'manufacturer' => 'GlaxoSmithKline',   'next_dose_months' => 6],
        ['vaccine_name' => 'Varicella Vaccine',                  'manufacturer' => 'Merck',             'next_dose_months' => null],
        ['vaccine_name' => 'HPV Vaccine (Gardasil)',             'manufacturer' => 'Merck',             'next_dose_months' => 2],
        ['vaccine_name' => 'Meningococcal Vaccine',              'manufacturer' => 'Pfizer',            'next_dose_months' => 60],
    ];

    // ─── Blood test parameter pool ────────────────────────────────────────────────
    private array $paramPool = [
        ['name' => 'Hemoglobin',      'min' => 100,  'max' => 175,  'divisor' => 10,  'unit' => 'g/dL',       'range' => '12.0-17.5 g/dL',       'flag_threshold' => 120],
        ['name' => 'WBC',             'min' => 35,   'max' => 120,  'divisor' => 10,  'unit' => '10^3/µL',    'range' => '4.5-11.0 10^3/µL',     'flag_threshold' => 115],
        ['name' => 'Platelets',       'min' => 100,  'max' => 500,  'divisor' => 1,   'unit' => '10^3/µL',    'range' => '150-400 10^3/µL',       'flag_threshold' => 420],
        ['name' => 'Sodium',          'min' => 130,  'max' => 148,  'divisor' => 1,   'unit' => 'mEq/L',      'range' => '135-145 mEq/L',         'flag_threshold' => 146],
        ['name' => 'Potassium',       'min' => 30,   'max' => 58,   'divisor' => 10,  'unit' => 'mEq/L',      'range' => '3.5-5.0 mEq/L',         'flag_threshold' => 52],
        ['name' => 'Creatinine',      'min' => 55,   'max' => 150,  'divisor' => 100, 'unit' => 'mg/dL',      'range' => '0.6-1.2 mg/dL',         'flag_threshold' => 125],
        ['name' => 'Blood Glucose',   'min' => 70,   'max' => 280,  'divisor' => 1,   'unit' => 'mg/dL',      'range' => '70-100 mg/dL',           'flag_threshold' => 126],
        ['name' => 'HbA1c',           'min' => 45,   'max' => 120,  'divisor' => 10,  'unit' => '%',          'range' => '< 5.7%',                 'flag_threshold' => 62],
        ['name' => 'TSH',             'min' => 2,    'max' => 150,  'divisor' => 10,  'unit' => 'mIU/L',      'range' => '0.4-4.0 mIU/L',         'flag_threshold' => 45],
        ['name' => 'ALT',             'min' => 7,    'max' => 80,   'divisor' => 1,   'unit' => 'U/L',        'range' => '7-56 U/L',               'flag_threshold' => 57],
        ['name' => 'AST',             'min' => 10,   'max' => 75,   'divisor' => 1,   'unit' => 'U/L',        'range' => '10-40 U/L',              'flag_threshold' => 41],
        ['name' => 'Troponin I',      'min' => 1,    'max' => 60,   'divisor' => 100, 'unit' => 'ng/mL',      'range' => '< 0.04 ng/mL',          'flag_threshold' => 5],
        ['name' => 'CRP',             'min' => 1,    'max' => 200,  'divisor' => 1,   'unit' => 'mg/L',       'range' => '< 10 mg/L',             'flag_threshold' => 11],
        ['name' => 'LDL Cholesterol', 'min' => 80,   'max' => 220,  'divisor' => 1,   'unit' => 'mg/dL',      'range' => '< 130 mg/dL',           'flag_threshold' => 131],
        ['name' => 'HDL Cholesterol', 'min' => 30,   'max' => 90,   'divisor' => 1,   'unit' => 'mg/dL',      'range' => '> 40 mg/dL',            'flag_threshold' => 39],
        ['name' => 'eGFR',            'min' => 30,   'max' => 120,  'divisor' => 1,   'unit' => 'mL/min',     'range' => '> 60 mL/min',           'flag_threshold' => 59],
    ];

    // =========================================================================
    public function run(): void
    // =========================================================================
    {
        if (DB::table('medical_history')->count() > 0) {
            $this->command->info('MedicalHistorySeeder already run. Skipping.');
            return;
        }

        $approvedDoctors = Doctor::where('is_approved', true)->pluck('id')->toArray();
        $allPatients     = Patient::orderBy('id')->pluck('id')->toArray();

        if (empty($approvedDoctors) || empty($allPatients)) {
            $this->command->warn('No approved doctors or patients found. Run DoctorSeeder and PatientSeeder first.');
            return;
        }

        $total = count($allPatients);

        // ─── Group 1: First 100 patients – 1-4 histories each ────────────────────
        $group1 = array_slice($allPatients, 0, min(100, $total));
        foreach ($group1 as $patientId) {
            $num = rand(1, 4);
            for ($i = 0; $i < $num; $i++) {
                $this->createHistoryRecord(
                    $patientId,
                    $this->conditions[array_rand($this->conditions)],
                    Carbon::now()->subDays(rand(1, 1095)),
                    $approvedDoctors[array_rand($approvedDoctors)]
                );
            }
        }
        $this->command->info("Group 1 done ({$this->historyCount} histories so far).");

        // ─── Group 2: Patients 101-130 – 3-8 histories over 2 years ─────────────
        $group2 = array_slice($allPatients, 100, 30);
        foreach ($group2 as $patientId) {
            $num = rand(3, 8);
            for ($i = 0; $i < $num; $i++) {
                $this->createHistoryRecord(
                    $patientId,
                    $this->conditions[array_rand($this->conditions)],
                    Carbon::now()->subDays(rand(1, 730)),
                    $approvedDoctors[array_rand($approvedDoctors)]
                );
            }
        }
        $this->command->info("Group 2 done ({$this->historyCount} histories so far).");

        // ─── Group 3: High-history patient – 100 records over 3 years ────────────
        $highPatient = Patient::orderBy('id')->skip(120)->first();
        if ($highPatient) {
            for ($i = 0; $i < 100; $i++) {
                $this->createHistoryRecord(
                    $highPatient->id,
                    $this->conditions[$i % count($this->conditions)],
                    Carbon::now()->subDays(rand(1, 1095)),
                    $approvedDoctors[array_rand($approvedDoctors)]
                );
            }
            $this->command->info("High-history patient (ID: {$highPatient->id}) seeded with 100 records.");
        }

        // Group 4 (patients 131 to total-20) gets NO history – edge-case coverage.
        // Group 5 (last 20 patients) is also intentionally skipped.

        // ─── Surgeries & immunizations ────────────────────────────────────────────
        $surgeryPool = array_slice($allPatients, 5, min(80, $total));
        foreach ($surgeryPool as $patientId) {
            if (rand(1, 100) <= 40) {
                $num = rand(1, 3);
                for ($s = 0; $s < $num; $s++) {
                    $surgery = $this->surgeryCatalogue[array_rand($this->surgeryCatalogue)];
                    $surgDate = Carbon::now()->subDays(rand(30, 1825));
                    $this->surgeriesBatch[] = [
                        'patient_id'    => $patientId,
                        'surgery_name'  => $surgery['surgery_name'],
                        'surgery_date'  => $surgDate->toDateString(),
                        'hospital'      => $surgery['hospital'],
                        'surgeon_name'  => $surgery['surgeon_name'],
                        'reason'        => $surgery['reason'],
                        'complications' => rand(1, 5) === 1 ? 'Minor post-operative bleeding, managed conservatively and resolved.' : null,
                        'notes'         => rand(0, 1) ? 'Patient recovered uneventfully. Discharged in stable condition.' : null,
                        'created_at'    => now()->toDateTimeString(),
                        'updated_at'    => now()->toDateTimeString(),
                    ];
                }
            }
        }

        $immunPool = array_slice($allPatients, 0, min(90, $total));
        foreach ($immunPool as $patientId) {
            $num = rand(1, 2);
            for ($im = 0; $im < $num; $im++) {
                $vaccine   = $this->vaccineCatalogue[array_rand($this->vaccineCatalogue)];
                $adminDate = Carbon::now()->subDays(rand(30, 730));
                $nextDate  = $vaccine['next_dose_months']
                    ? $adminDate->copy()->addMonths($vaccine['next_dose_months'])->toDateString()
                    : null;
                $this->immunizationsBatch[] = [
                    'patient_id'          => $patientId,
                    'doctor_id'           => $approvedDoctors[array_rand($approvedDoctors)],
                    'vaccine_name'        => $vaccine['vaccine_name'],
                    'administration_date' => $adminDate->toDateString(),
                    'next_dose_date'      => $nextDate,
                    'lot_number'          => strtoupper(substr(md5((string) rand()), 0, 8)),
                    'manufacturer'        => $vaccine['manufacturer'],
                    'notes'               => null,
                    'created_at'          => now()->toDateTimeString(),
                    'updated_at'          => now()->toDateTimeString(),
                ];
            }
        }

        // ─── Flush all batches ────────────────────────────────────────────────────
        $this->flushBatches();

        $this->command->info(
            "MedicalHistorySeeder completed. " .
            "Histories: {$this->historyCount} | " .
            "VitalSigns: " . count($this->vitalSignsBatch) . " | " .
            "Diagnoses: " . count($this->diagnosesBatch) . " | " .
            "Surgeries: " . count($this->surgeriesBatch) . " | " .
            "Immunizations: " . count($this->immunizationsBatch)
        );
    }

    // =========================================================================
    // Core record builder
    // =========================================================================
    private function createHistoryRecord(
        int    $patientId,
        array  $condition,
        Carbon $visitDate,
        int    $doctorId
    ): void {
        $pastHistory  = rand(0, 1)
            ? 'No significant past medical history.'
            : 'Known hypertension and Type 2 Diabetes Mellitus, on regular medications.';
        $familyHx     = rand(0, 2) > 0
            ? 'Family history of cardiovascular disease and hypertension.'
            : null;
        $socialHx     = rand(0, 1)
            ? 'Non-smoker, occasional alcohol use, sedentary lifestyle.'
            : 'Former smoker (20 pack-years), quit 5 years ago, construction worker.';

        $historyId = DB::table('medical_history')->insertGetId([
            'patient_id'              => $patientId,
            'doctor_id'               => $doctorId,
            'visit_date'              => $visitDate->toDateString(),
            'chief_complaint'         => $condition['chief_complaint'],
            'present_illness_history' => $condition['present_illness'],
            'past_medical_history'    => $pastHistory,
            'family_history'          => $familyHx,
            'social_history'          => $socialHx,
            'allergies'               => $condition['allergen']
                ? "{$condition['allergen']}: {$condition['reaction']}"
                : null,
            'current_medications'     => $condition['medications'],
            'physical_examination'    => $this->getPhysicalExam($condition['visit_type']),
            'diagnosis'               => $condition['diagnosis'],
            'treatment_plan'          => $condition['treatment_plan'],
            'doctor_notes'            => 'Patient counseled on condition management and lifestyle modifications. Follow-up appointment scheduled as recommended.',
            'visit_type'              => $condition['visit_type'],
            'status'                  => $condition['status'],
            'created_at'              => now()->toDateTimeString(),
            'updated_at'              => now()->toDateTimeString(),
        ]);

        $this->historyCount++;

        // Vital sign
        $this->vitalSignsBatch[] = $this->buildVitalSign($patientId, $historyId, $condition, $visitDate);

        // Primary diagnosis
        $this->diagnosesBatch[] = [
            'patient_id'         => $patientId,
            'doctor_id'          => $doctorId,
            'medical_history_id' => $historyId,
            'icd_code'           => $condition['icd_code'],
            'diagnosis_name'     => $condition['diagnosis'],
            'description'        => 'Diagnosed based on clinical presentation, history, and relevant investigations.',
            'certainty'          => 'confirmed',
            'diagnosis_date'     => $visitDate->toDateString(),
            'notes'              => null,
            'created_at'         => now()->toDateTimeString(),
            'updated_at'         => now()->toDateTimeString(),
        ];

        // Secondary/differential diagnosis (30% of records)
        if (rand(1, 100) <= 30) {
            $secondary = $this->conditions[array_rand($this->conditions)];
            $this->diagnosesBatch[] = [
                'patient_id'         => $patientId,
                'doctor_id'          => $doctorId,
                'medical_history_id' => $historyId,
                'icd_code'           => $secondary['icd_code'],
                'diagnosis_name'     => $secondary['diagnosis'],
                'description'        => 'Secondary differential to be monitored.',
                'certainty'          => 'possible',
                'diagnosis_date'     => $visitDate->toDateString(),
                'notes'              => 'Further workup may be required.',
                'created_at'         => now()->toDateTimeString(),
                'updated_at'         => now()->toDateTimeString(),
            ];
        }

        // Allergy (if condition has one)
        if ($condition['allergen']) {
            $this->allergiesBatch[] = [
                'patient_id'     => $patientId,
                'allergen'       => $condition['allergen'],
                'reaction'       => $condition['reaction'],
                'severity'       => $condition['allergy_severity'],
                'diagnosed_date' => $visitDate->toDateString(),
                'notes'          => 'Documented during visit. Patient advised to carry medical alert card.',
                'created_at'     => now()->toDateTimeString(),
                'updated_at'     => now()->toDateTimeString(),
            ];
        }

        // Lab test (50% of visits)
        if (rand(0, 1)) {
            $labTestId = DB::table('lab_tests')->insertGetId([
                'patient_id'         => $patientId,
                'doctor_id'          => $doctorId,
                'medical_history_id' => $historyId,
                'test_name'          => $this->getLabTestName($condition['diagnosis']),
                'test_category'      => $this->getTestCategory($condition['diagnosis']),
                'test_date'          => $visitDate->toDateString(),
                'result_date'        => $visitDate->copy()->addDays(rand(1, 3))->toDateString(),
                'results'            => 'Results as detailed in individual parameters below.',
                'reference_range'    => 'See individual parameters.',
                'interpretation'     => rand(0, 2) ? 'Abnormal findings noted, clinical correlation required.' : 'Within normal limits.',
                'status'             => 'completed',
                'file_path'          => null,
                'technician_notes'   => null,
                'created_at'         => now()->toDateTimeString(),
                'updated_at'         => now()->toDateTimeString(),
            ]);

            // Shuffle param pool and pick 3-6 unique parameters
            $pool   = $this->paramPool;
            shuffle($pool);
            $params = array_slice($pool, 0, rand(3, 6));

            foreach ($params as $p) {
                $rawValue = rand((int) $p['min'], (int) $p['max']) / $p['divisor'];
                $value    = round($rawValue, 2);
                $flag     = $value > ($p['flag_threshold'] / $p['divisor']) ? 'H' : null;

                $this->bloodParamsBatch[] = [
                    'lab_test_id'     => $labTestId,
                    'parameter_name'  => $p['name'],
                    'value'           => $value,
                    'unit'            => $p['unit'],
                    'reference_range' => $p['range'],
                    'flag'            => $flag,
                    'created_at'      => now()->toDateTimeString(),
                    'updated_at'      => now()->toDateTimeString(),
                ];
            }
        }
    }

    // =========================================================================
    // Flush all batches via bulk inserts
    // =========================================================================
    private function flushBatches(): void
    {
        foreach (array_chunk($this->vitalSignsBatch, 100) as $chunk) {
            DB::table('vital_signs')->insert($chunk);
        }
        foreach (array_chunk($this->diagnosesBatch, 100) as $chunk) {
            DB::table('diagnoses')->insert($chunk);
        }
        foreach (array_chunk($this->allergiesBatch, 100) as $chunk) {
            DB::table('allergies')->insert($chunk);
        }
        foreach (array_chunk($this->bloodParamsBatch, 100) as $chunk) {
            DB::table('blood_test_parameters')->insert($chunk);
        }
        foreach (array_chunk($this->surgeriesBatch, 100) as $chunk) {
            DB::table('surgeries')->insert($chunk);
        }
        foreach (array_chunk($this->immunizationsBatch, 100) as $chunk) {
            DB::table('immunizations')->insert($chunk);
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function buildVitalSign(int $patientId, int $historyId, array $condition, Carbon $date): array
    {
        $isEmergency  = $condition['visit_type'] === 'emergency';
        $isHypertension = str_contains($condition['diagnosis'], 'Hypertension');
        $isDiabetes   = str_contains($condition['diagnosis'], 'Diabetes');

        $temperature  = round(($isEmergency ? 37.8 : 36.5) + rand(-5, 15) / 10, 2);
        $heartRate    = rand(60, 90) + ($isEmergency ? rand(20, 40) : 0);
        $respRate     = rand(12, 18) + ($isEmergency ? rand(4, 8) : 0);
        $bpSystolic   = rand(110, 130) + ($isHypertension ? rand(30, 60) : rand(0, 10));
        $bpDiastolic  = rand(70, 85)   + ($isHypertension ? rand(15, 30) : rand(0, 5));
        $o2Sat        = round($isEmergency ? rand(880, 960) / 10 : rand(960, 1000) / 10, 2);
        $height       = round(rand(1550, 1900) / 10, 2);
        $weight       = round(rand(550, 1100) / 10, 2);
        $bmi          = round($weight / (($height / 100) ** 2), 2);
        $bloodGlucose = round(($isDiabetes ? rand(120, 280) : rand(70, 110)) + rand(0, 9) / 10, 2);

        return [
            'patient_id'               => $patientId,
            'medical_history_id'       => $historyId,
            'temperature'              => $temperature,
            'heart_rate'               => $heartRate,
            'respiratory_rate'         => $respRate,
            'blood_pressure_systolic'  => $bpSystolic,
            'blood_pressure_diastolic' => $bpDiastolic,
            'oxygen_saturation'        => $o2Sat,
            'height'                   => $height,
            'weight'                   => $weight,
            'bmi'                      => $bmi,
            'blood_glucose'            => $bloodGlucose,
            'measured_at'              => $date->toDateTimeString(),
            'created_at'               => now()->toDateTimeString(),
            'updated_at'               => now()->toDateTimeString(),
        ];
    }

    private function getPhysicalExam(string $visitType): string
    {
        return match ($visitType) {
            'emergency'    => 'Patient in acute distress. Vitals unstable on arrival. IV access established immediately. Airway patent, breathing laboured. Continuous cardiac monitoring initiated.',
            'follow_up'    => 'Patient appears in improved condition compared to last visit. Vitals stable. Pertinent system examination performed with findings documented. No acute distress noted.',
            'consultation' => 'Comprehensive systematic examination performed. Patient cooperative and oriented x3. Specialist findings documented for referral. No immediate emergency findings.',
            default        => 'General: Alert and oriented x3, in no acute distress. CVS: Regular rate and rhythm, no murmurs. Resp: Clear bilaterally. Abdomen: Soft, non-tender, non-distended. Extremities: No oedema.',
        };
    }

    private function getLabTestName(string $diagnosis): string
    {
        return match (true) {
            str_contains($diagnosis, 'Coronary')   => 'Cardiac Enzyme Panel',
            str_contains($diagnosis, 'Diabetes')   => 'Complete Metabolic Panel with HbA1c',
            str_contains($diagnosis, 'Hypertension')=> 'Renal Function & Electrolytes Panel',
            str_contains($diagnosis, 'Asthma')     => 'Arterial Blood Gas Analysis',
            str_contains($diagnosis, 'Osteoarthritis') => 'Inflammatory Markers Panel (ESR/CRP)',
            str_contains($diagnosis, 'Pneumonia')  => 'Complete Blood Count with Differential',
            str_contains($diagnosis, 'GERD')        => 'H. Pylori Antigen Test',
            str_contains($diagnosis, 'Depressive') => 'Thyroid Function & Full Blood Count',
            str_contains($diagnosis, 'Hypothyroid')=> 'Thyroid Function Panel (TSH, T3, T4)',
            str_contains($diagnosis, 'Lumbar')     => 'Complete Blood Count',
            default                                 => 'Complete Blood Count',
        };
    }

    private function getTestCategory(string $diagnosis): string
    {
        return match (true) {
            str_contains($diagnosis, 'Coronary'),
            str_contains($diagnosis, 'Hypertension') => 'Cardiology',
            str_contains($diagnosis, 'Diabetes'),
            str_contains($diagnosis, 'Hypothyroid') => 'Endocrinology',
            str_contains($diagnosis, 'Asthma'),
            str_contains($diagnosis, 'Pneumonia')   => 'Pulmonology',
            str_contains($diagnosis, 'Depressive')  => 'Psychiatry',
            default                                  => 'Hematology',
        };
    }
}
