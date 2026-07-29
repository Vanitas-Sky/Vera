<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $uuid
 * @property string $rfc_issuer
 * @property string $name_issuer
 * @property string $rfc_receiver
 * @property string $name_receiver
 * @property string $invoice_type
 * @property \Illuminate\Support\Carbon $issue_date
 * @property string $subtotal
 * @property string $vat_amount
 * @property string $vat_retention
 * @property string $isr_retention
 * @property string $total
 * @property string|null $payment_method_code
 * @property string|null $payment_form_code
 * @property string|null $cfdi_use_code
 * @property string $deductibility_status
 * @property string|null $raw_xml_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SatCfdiUse|null $cfdiUse
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CfdiItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\SatPaymentForm|null $paymentForm
 * @property-read \App\Models\SatPaymentMethod|null $paymentMethod
 * @method static \Database\Factories\CfdiFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi query()
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereCfdiUseCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereDeductibilityStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereInvoiceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereIsrRetention($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereNameIssuer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereNameReceiver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi wherePaymentFormCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi wherePaymentMethodCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereRawXmlPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereRfcIssuer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereRfcReceiver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereVatAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cfdi whereVatRetention($value)
 */
	class Cfdi extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cfdi_id
 * @property string $sat_product_service_code
 * @property int $item_number
 * @property string $original_description
 * @property string|null $nlp_interpreted_category
 * @property string $quantity
 * @property string $unit_price
 * @property string $subtotal
 * @property string $vat_amount
 * @property string $deductibility_status
 * @property-read \App\Models\Cfdi $cfdi
 * @property-read \App\Models\SatProductService $satProductService
 * @method static \Database\Factories\CfdiItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem query()
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereCfdiId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereDeductibilityStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereItemNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereNlpInterpretedCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereOriginalDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereSatProductServiceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CfdiItem whereVatAmount($value)
 */
	class CfdiItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $rfc
 * @property string $legal_name
 * @property string|null $trade_name
 * @property string $postal_code
 * @property string $tax_regime_code
 * @property string|null $pac_api_key_sandbox
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SatEconomicActivity> $economicActivities
 * @property-read int|null $economic_activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserCompany> $userCompanies
 * @property-read int|null $user_companies_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\CompanyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company wherePacApiKeySandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereTaxRegimeCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereTradeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereUpdatedAt($value)
 */
	class Company extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $rfc
 * @property string $curp
 * @property string $full_name
 * @property string $base_salary
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayrollDetail> $payrollDetails
 * @property-read int|null $payroll_details_count
 * @method static \Database\Factories\EmployeeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder|Employee whereBaseSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Employee whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Employee whereCurp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Employee whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Employee whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Employee whereRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Employee whereUpdatedAt($value)
 */
	class Employee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $provider_name
 * @property string $category
 * @property string|null $description
 * @property string $monthly_amount
 * @property int $due_day
 * @property \Illuminate\Support\Carbon|null $contract_start_date
 * @property \Illuminate\Support\Carbon|null $contract_end_date
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @method static \Database\Factories\FixedExpenseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense query()
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereContractEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereContractStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereDueDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereMonthlyAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereProviderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FixedExpense whereUpdatedAt($value)
 */
	class FixedExpense extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $sat_product_service_code
 * @property string $deductibility_status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\SatProductService $satProductService
 * @method static \Database\Factories\IndispensabilityMatrixFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix query()
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix whereDeductibilityStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix whereSatProductServiceCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IndispensabilityMatrix whereUpdatedAt($value)
 */
	class IndispensabilityMatrix extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $period_year
 * @property int $period_month
 * @property string $executive_summary
 * @property array $recommendations_json
 * @property \Illuminate\Support\Carbon $generated_at
 * @property-read \App\Models\Company $company
 * @method static \Database\Factories\LlmConsultantReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport query()
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport whereExecutiveSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport whereGeneratedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport wherePeriodMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport wherePeriodYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LlmConsultantReport whereRecommendationsJson($value)
 */
	class LlmConsultantReport extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $alert_type
 * @property string $priority
 * @property string $title
 * @property string $message
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Company $company
 * @method static \Database\Factories\NotificationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification query()
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereAlertType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Notification whereTitle($value)
 */
	class Notification extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $payroll_period_id
 * @property int $employee_id
 * @property string $gross_salary
 * @property string $isr_retention
 * @property string $imss_employee
 * @property string $net_salary
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\PayrollPeriod $payrollPeriod
 * @method static \Database\Factories\PayrollDetailFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail whereGrossSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail whereImssEmployee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail whereIsrRetention($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail whereNetSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollDetail wherePayrollPeriodId($value)
 */
	class PayrollDetail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $period_name
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string $total_gross
 * @property string $total_isr_retention
 * @property string $total_imss_employee
 * @property string $total_imss_employer
 * @property string $total_net
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PayrollDetail> $details
 * @property-read int|null $details_count
 * @method static \Database\Factories\PayrollPeriodFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod query()
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod wherePeriodName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereTotalGross($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereTotalImssEmployee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereTotalImssEmployer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereTotalIsrRetention($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereTotalNet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PayrollPeriod whereUpdatedAt($value)
 */
	class PayrollPeriod extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property int $period_year
 * @property int $period_month
 * @property int $risk_score
 * @property array|null $anomalies_detected_json
 * @property \Illuminate\Support\Carbon $evaluated_at
 * @property-read \App\Models\Company $company
 * @method static \Database\Factories\RiskEvaluationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation query()
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation whereAnomaliesDetectedJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation whereEvaluatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation wherePeriodMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation wherePeriodYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RiskEvaluation whereRiskScore($value)
 */
	class RiskEvaluation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\RoleFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $code
 * @property string $description
 * @method static \Database\Factories\SatCfdiUseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SatCfdiUse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatCfdiUse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatCfdiUse query()
 * @method static \Illuminate\Database\Eloquent\Builder|SatCfdiUse whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SatCfdiUse whereDescription($value)
 */
	class SatCfdiUse extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Company> $companies
 * @property-read int|null $companies_count
 * @method static \Database\Factories\SatEconomicActivityFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SatEconomicActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatEconomicActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatEconomicActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|SatEconomicActivity whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SatEconomicActivity whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SatEconomicActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SatEconomicActivity whereName($value)
 */
	class SatEconomicActivity extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $code
 * @property string $description
 * @method static \Database\Factories\SatPaymentFormFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentForm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentForm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentForm query()
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentForm whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentForm whereDescription($value)
 */
	class SatPaymentForm extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $code
 * @property string $description
 * @method static \Database\Factories\SatPaymentMethodFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentMethod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentMethod query()
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentMethod whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SatPaymentMethod whereDescription($value)
 */
	class SatPaymentMethod extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $code
 * @property string $description
 * @property string|null $similar_words
 * @method static \Database\Factories\SatProductServiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SatProductService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatProductService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatProductService query()
 * @method static \Illuminate\Database\Eloquent\Builder|SatProductService whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SatProductService whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SatProductService whereSimilarWords($value)
 */
	class SatProductService extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $code
 * @property string $description
 * @method static \Database\Factories\SatTaxRegimeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SatTaxRegime newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatTaxRegime newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SatTaxRegime query()
 * @method static \Illuminate\Database\Eloquent\Builder|SatTaxRegime whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SatTaxRegime whereDescription($value)
 */
	class SatTaxRegime extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $test_uuid
 * @property string $receiver_rfc
 * @property string $receiver_name
 * @property string $receiver_postal_code
 * @property string $receiver_tax_regime_code
 * @property string|null $cfdi_use_code
 * @property string|null $payment_method_code
 * @property string|null $payment_form_code
 * @property string $total
 * @property string|null $pdf_sandbox_path
 * @property string|null $xml_sandbox_path
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\SatCfdiUse|null $cfdiUse
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\SatPaymentForm|null $paymentForm
 * @property-read \App\Models\SatPaymentMethod|null $paymentMethod
 * @method static \Database\Factories\SimulatedInvoiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice query()
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereCfdiUseCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice wherePaymentFormCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice wherePaymentMethodCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice wherePdfSandboxPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereReceiverName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereReceiverPostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereReceiverRfc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereReceiverTaxRegimeCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereTestUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SimulatedInvoice whereXmlSandboxPath($value)
 */
	class SimulatedInvoice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $role_id
 * @property string $name
 * @property string $email
 * @property string|null $full_name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property mixed $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Company> $companies
 * @property-read int|null $companies_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Role|null $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserCompany> $userCompanies
 * @property-read int|null $user_companies_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $company_id
 * @property string $role_in_company
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\UserCompanyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|UserCompany newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCompany newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCompany query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserCompany whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCompany whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCompany whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCompany whereRoleInCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCompany whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserCompany whereUserId($value)
 */
	class UserCompany extends \Eloquent {}
}

