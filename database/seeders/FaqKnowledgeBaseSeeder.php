<?php

namespace Database\Seeders;

use App\Models\FaqArticle;
use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqKnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'General', 'description' => 'General customer support questions', 'sort_order' => 1],
            ['name' => 'Policies', 'description' => 'Cover, policy documents and endorsements', 'sort_order' => 2],
            ['name' => 'Premiums & Payments', 'description' => 'Billing, receipts and payment methods', 'sort_order' => 3],
            ['name' => 'Claims', 'description' => 'Claim lodging, requirements and status', 'sort_order' => 4],
            ['name' => 'Renewals', 'description' => 'Policy and product renewals', 'sort_order' => 5],
            ['name' => 'Complaints', 'description' => 'Complaint handling and escalation', 'sort_order' => 6],
            ['name' => 'Client Portal', 'description' => 'Online portal login and self-service', 'sort_order' => 7],
            ['name' => 'KYC & Onboarding', 'description' => 'Identity checks and new client capture', 'sort_order' => 8],
            ['name' => 'Agents & Intermediaries', 'description' => 'Agent support and commission queries', 'sort_order' => 9],
        ];

        $catIds = [];
        foreach ($categories as $c) {
            $cat = FaqCategory::firstOrCreate(
                ['name' => $c['name']],
                [
                    'slug' => FaqCategory::uniqueSlug($c['name']),
                    'description' => $c['description'],
                    'sort_order' => $c['sort_order'],
                ]
            );
            $cat->fill([
                'description' => $c['description'],
                'sort_order' => $c['sort_order'],
            ])->save();
            $catIds[$c['name']] = $cat->id;
        }

        $articles = [
            ['General', 'What are your customer service hours?', "Our support desk operates Monday to Friday, 8:00am to 5:00pm. Urgent claims can be logged 24/7 through the emergency line printed on the policy document.", 'hours,support,contact'],
            ['General', 'How do I create a support ticket for a client?', "Open the client record, confirm the policy number, then click Create Ticket. Capture category, priority, and a clear description. Assign to the correct department and note any SLA turnaround.", 'ticket,create,support'],
            ['Policies', 'How can a client get a copy of their policy document?', "Confirm the client's identity and policy number, then use the client record to email the policy schedule. If the document is not on file, request it from Underwriting and share once received.", 'policy,document,schedule'],
            ['Policies', 'How do I add or change cover on an existing policy?', "Raise an endorsement request via the policy record. Capture the change requested, effective date and any supporting documents, then route to Underwriting for rating and issuance.", 'endorsement,cover,change'],
            ['Policies', 'What is a bonus certificate?', "A bonus certificate confirms declared bonuses on participating life policies. Verify the policy is participating and active, then request the certificate from Actuarial / Policy Admin and send it to the client.", 'bonus,certificate,life'],
            ['Premiums & Payments', 'What payment methods are accepted for premiums?', "Clients can pay via M-Pesa (paybill on the invoice), bank transfer, cheque, or at any branch. Always confirm the receipt/transaction reference and update the policy record.", 'payment,mpesa,premium'],
            ['Premiums & Payments', 'A client says a payment is not reflected. What do I do?', "Ask for the transaction reference and date, verify against the receipts/premiums tab, and if still missing escalate to Finance with the proof of payment for reconciliation.", 'payment,receipt,reconciliation'],
            ['Premiums & Payments', 'How do I process a premium refund?', "Confirm the overpayment or cancellation reason, raise a Premium Refund request with Finance, attach proof, and keep the client updated until the refund is paid.", 'refund,premium,finance'],
            ['Claims', 'What documents are required to lodge a claim?', "Standard requirements are the completed claim form, a copy of the policy, ID, and supporting evidence (police abstract, medical report, or invoices depending on claim type). Verify the policy is active and premiums are up to date before proceeding.", 'claims,documents,requirements'],
            ['Claims', 'How can a client check the status of their claim?', "Look up the claim on the client record and share the current stage. If pending with Claims department beyond SLA, escalate and give the client a realistic follow-up date.", 'claims,status'],
            ['Claims', 'How do I handle a disability claim enquiry?', "Confirm the policy has disability benefit, collect medical reports and employer confirmation where required, lodge the claim under Disability Claim category, and assign to Claims with the correct TAT.", 'disability,claim'],
            ['Renewals', 'How do I handle a policy due for renewal?', "Open the Renewals screen, select the product type (Individual, Group, Pension, Annuities), and contact the client by email or SMS with the renewal terms before the due date. Log the outreach and create a ticket if follow-up is needed.", 'renewal,reminder,product'],
            ['Renewals', 'What if a client wants to change product at renewal?', "Capture the requested product change, check underwriting requirements, and create a ticket for Underwriting / Business Development. Do not promise terms until a quote is issued.", 'renewal,product,change'],
            ['Complaints', 'When should an issue be logged as a complaint?', "Log a complaint whenever a client expresses dissatisfaction with service, a product, or a delay. Record it in the Complaint Register with the nature, policy number and complainant details, and assign it for resolution.", 'complaint,register,escalation'],
            ['Complaints', 'What is the turnaround for acknowledging a complaint?', "Acknowledge within one business day, log in the Complaint Register, assign an owner, and give the client a reference number plus expected resolution timeline per department TAT.", 'complaint,acknowledge,sla'],
            ['Client Portal', 'A client cannot log in to the client portal. What should I do?', "Verify their registered email/mobile, reset the portal password if available, and confirm the policy is active. If still blocked, raise a Client Portal ticket to IT with the username and error message.", 'portal,login,password'],
            ['Client Portal', 'How does a client download statements from the portal?', "Guide them to log in → Policies → select policy → Statements / Documents. If documents are missing, request them from Policy Admin and upload or email the client.", 'portal,statements,documents'],
            ['KYC & Onboarding', 'What KYC details are required to create a new client?', "Capture full name, ID/passport, KRA PIN, date of birth, gender, phone, email, address, occupation, product, and intermediary. Incomplete KYC should be flagged before policy issuance.", 'kyc,onboarding,create-client'],
            ['KYC & Onboarding', 'How do I import many new clients at once?', "Use Clients → Import, download the CSV template, fill required columns, and upload. Duplicate policy numbers are skipped. Review the import summary for errors.", 'import,csv,clients'],
            ['Agents & Intermediaries', 'How do I find the agent on a policy?', "Open the client/policy record and check the Intermediary / Agent field. For local CRM clients, the agent is selected at capture from the Agents list.", 'agent,intermediary'],
            ['Agents & Intermediaries', 'An agent asks about commission status. What next?', "Do not quote commission figures from Support unless authorised. Take the agent code and policy numbers, create a ticket for Finance / BD, and share the ticket reference.", 'agent,commission'],
        ];

        foreach ($articles as [$catName, $question, $answer, $tags]) {
            FaqArticle::updateOrCreate(
                ['question' => $question],
                [
                    'faq_category_id' => $catIds[$catName] ?? null,
                    'answer' => $answer,
                    'tags' => $tags,
                    'status' => 'published',
                    'created_by_name' => 'System',
                ]
            );
        }
    }
}
