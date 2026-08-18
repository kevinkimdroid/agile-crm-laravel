<?php
    $detailFields = [
        ['Last Name', $contact->lastname ?? null],
        ['First Name', $contact->firstname ?? null],
        ['Id Number', $contact->idNumber ?? $contact->id_number ?? null],
        ['Policy Number', $contact->policy_number ?? null],
        ['Prospect Id', $contact->contact_no ?? null],
        ['PIN', $contact->pin ?? $contact->kra_pin ?? null],
        ['Office Phone', $contact->phone ?? null],
        ['Mobile Phone', $contact->mobile ?? null],
        ['Home Phone', $contact->homephone ?? null],
        ['Primary Email', $contact->email ?? null],
        ['Secondary Email', $contact->secondaryemail ?? $contact->otheremail ?? null],
        ['Title', $contact->title ?? null],
        ['Department', $contact->department ?? null],
        ['Fax', $contact->fax ?? null],
        ['Date of Birth', !empty($contact->birthday) ? date('d-m-Y', strtotime($contact->birthday)) : null],
        ['Lead Source', $contact->leadsource ?? null],
        ['Estimated Business Worth', $contact->lead_business_worth ?? $contact->cf_872 ?? null],
        ['Mailing Address', collect([
            $contact->mailingstreet ?? null,
            $contact->mailingcity ?? null,
            $contact->mailingstate ?? null,
            $contact->mailingzip ?? null,
            $contact->mailingcountry ?? null,
        ])->filter()->implode(', ') ?: null],
        ['P.O. Box', $contact->mailingpobox ?? null],
        ['Reports To', $contact->reportsto ?? null],
        ['Do Not Call', isset($contact->donotcall) ? ($contact->donotcall ? 'Yes' : 'No') : null],
        ['Email Opt Out', isset($contact->emailoptout) ? ($contact->emailoptout ? 'Yes' : 'No') : null],
        ['Reference', isset($contact->reference) ? ($contact->reference ? 'Yes' : 'No') : null],
        ['Assigned To', $contact->assigned_to_name ?? null],
        ['Notify Owner', isset($contact->notify_owner) ? ($contact->notify_owner ? 'Yes' : 'No') : null],
        ['Is Converted From Lead', isset($contact->isconvertedfromlead) ? ($contact->isconvertedfromlead ? 'Yes' : 'No') : null],
        ['Created Time', $contact->createdtime ? date('d-m-Y H:i', strtotime($contact->createdtime)) : null],
        ['Modified Time', $contact->modifiedtime ? date('d-m-Y H:i', strtotime($contact->modifiedtime)) : null],
        ['Source', $contact->source ?? null],
        ['Notes', $contact->description ?? null],
    ];
    $left = array_slice($detailFields, 0, (int) ceil(count($detailFields) / 2));
    $right = array_slice($detailFields, (int) ceil(count($detailFields) / 2));
    $displayValue = fn ($value) => ($value !== null && $value !== '') ? $value : '—';
?>

<div class="card contact-detail-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase small fw-bold text-muted mb-3">Basic Information</h6>
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <?php $__currentLoopData = $left; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <dt class="col-sm-5 text-muted small"><?php echo e($f[0]); ?></dt>
                    <dd class="col-sm-7 mb-2"><?php echo e($displayValue($f[1])); ?></dd>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <?php $__currentLoopData = $right; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <dt class="col-sm-5 text-muted small"><?php echo e($f[0]); ?></dt>
                    <dd class="col-sm-7 mb-2"><?php echo e($displayValue($f[1])); ?></dd>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </dl>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\sites\agile-crm-laravel\resources\views/contacts/partials/details-tab.blade.php ENDPATH**/ ?>