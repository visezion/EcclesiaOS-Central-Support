<?php

namespace Database\Seeders;

use App\Models\KnowledgeArticle;
use Illuminate\Database\Seeder;

final class ChurchMemberKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->articles() as $article) {
            KnowledgeArticle::query()->updateOrCreate(
                ['slug' => $article['slug']],
                [...$article, 'published' => true],
            );
        }
    }

    /** @return list<array{slug:string,title:string,category:string,body:string}> */
    private function articles(): array
    {
        return [
            [
                'slug' => 'getting-started-with-your-ecclesiaos-account',
                'title' => 'Getting started with your EcclesiaOS account',
                'category' => 'Getting Started',
                'body' => <<<'ARTICLE'
Who this is for
----------------
This guide is for church staff, volunteers, leaders, and members who are signing in to EcclesiaOS for the first time.

First sign-in
-------------
1. Open the church's official EcclesiaOS address. Check the address bar for HTTPS and confirm that the domain is the one provided by your church administrator.
2. Enter the email address assigned to your account and your password. Do not use another person's account, even when helping with a task.
3. If your church uses a remember-me option, use it only on a private device. Never enable it on a shared kiosk or public computer.
4. After sign-in, check the dashboard for your church name, campus, role, and any pending notifications.

Complete your profile
---------------------
Open your profile and confirm your display name, email address, phone number, campus, and notification preferences. Keep contact details current so church leaders can reach you about events, serving, pastoral care, or account security.

If you cannot sign in
---------------------
- Re-enter the email address carefully; an extra space can cause a failure.
- Use the password reset option instead of repeatedly guessing a password.
- Check your email spam or junk folder for the reset message.
- Ask a church administrator to confirm that your account is active and assigned to the correct church.
- Submit a private support ticket if the problem continues. Do not send your password, reset link, or one-time code in the ticket.

Good account habits
-------------------
Use a unique password, sign out of shared devices, keep your recovery email current, and report unexpected sign-in alerts immediately. Church administrators should disable accounts promptly when a staff or volunteer role ends.
ARTICLE,
            ],
            [
                'slug' => 'managing-member-information-and-privacy',
                'title' => 'Managing member information and privacy',
                'category' => 'Members & Privacy',
                'body' => <<<'ARTICLE'
Why accurate member records matter
-----------------------------------
Member records support pastoral care, communication, attendance, serving, household relationships, and reporting. A small correction made in the right record is safer than creating a duplicate profile.

Before creating a member
-------------------------
Search by name, email, phone number, and household before creating a new profile. Check alternate spellings and previous names. If a likely match exists, ask a church administrator to merge or correct the existing record rather than creating a duplicate.

Keep information purposeful
----------------------------
Only record information the church has a legitimate ministry or operational reason to keep. Use the correct fields for contact details, household relationships, campus, membership status, and communication preferences. Put sensitive pastoral details only in the approved confidential workflow, never in a general note or support ticket.

Privacy checklist
-----------------
- Share member information only with people whose role requires it.
- Do not export a full member list to a personal device unless the export is approved and protected.
- Never paste member names, phone numbers, addresses, medical information, payment details, or pastoral notes into Community Solutions or Live Support.
- Verify recipient addresses before sending messages.
- Remove downloaded files from personal devices after the approved task is complete.
- Report a suspected disclosure to the church administrator immediately.

Corrections and duplicates
--------------------------
Record what needs correction, identify the canonical profile, and avoid deleting history unless the church's retention policy permits it. For duplicates, preserve the profile with the best history and relationships, then have an authorized administrator merge or archive the duplicate.
ARTICLE,
            ],
            [
                'slug' => 'attendance-and-event-check-in-guide',
                'title' => 'Attendance and event check-in guide',
                'category' => 'Attendance & Events',
                'body' => <<<'ARTICLE'
Before the event
-----------------
Confirm the event date, campus, venue, session, age group, and attendance method. Make sure the roster is current and that volunteers have the minimum permission needed for check-in. Test the check-in device, network connection, printer, QR flow, or kiosk before people arrive.

During check-in
---------------
1. Search for the member or household using the approved search fields.
2. Confirm the identity before marking attendance. Do not select the first similar name without checking the campus or household.
3. Record visitors according to the church's visitor policy. Avoid creating a permanent member record unless the responsible team has consent and the required information.
4. For children and youth, follow the guardian, consent, pickup, and safeguarding procedures configured by the church.
5. Correct mistakes immediately where possible and leave an audit trail when a correction is made later.

After the event
---------------
Review totals for duplicates, missing sessions, and unexpected attendance. Check that the correct event and campus were used. Share reports only with authorized leaders. If attendance is offline, keep the approved temporary record secure and reconcile it once the service is available.

Common problems
---------------
- A member cannot be found: search alternate spelling and contact the membership administrator.
- A duplicate appears: stop and ask for a merge or record correction.
- A QR code fails: use the approved manual search and report the device or browser details in a private ticket.
- The wrong event was selected: do not create a second attendance record to compensate; ask an authorized administrator to correct the original.
ARTICLE,
            ],
            [
                'slug' => 'giving-and-financial-records-safety',
                'title' => 'Giving and financial records safety',
                'category' => 'Giving & Finance',
                'body' => <<<'ARTICLE'
Protect financial information
------------------------------
Giving records and payment details are sensitive. Never send card numbers, bank credentials, one-time payment codes, full receipts, or donor exports through Central Support, Community Solutions, email, or chat unless the church has an approved secure process.

Recording a gift
-----------------
Use the correct fund, date, amount, donor relationship, payment method, and campus. Review the entry before saving. If a gift is anonymous, follow the church's anonymous-giving procedure instead of attaching it to a guessed member.

Correcting an error
-------------------
Do not delete a financial record simply because it is wrong. Follow the church's adjustment, reversal, refund, or approval process so the audit history remains complete. Include the transaction reference—not the donor's payment credentials—when requesting help.

Member visibility
-----------------
Only authorized finance users should view giving history. A member may request their own statement through the approved church process, but staff must not disclose another person's giving information. Use role permissions and campus scope carefully.

If a payment looks suspicious
-----------------------------
Do not retry repeatedly or store payment details in notes. Record the safe transaction reference, time, amount, and visible error message, then escalate to the finance administrator or payment provider using the approved channel.
ARTICLE,
            ],
            [
                'slug' => 'church-messages-and-notification-preferences',
                'title' => 'Church messages and notification preferences',
                'category' => 'Communications',
                'body' => <<<'ARTICLE'
Choose the right audience
-------------------------
Before sending a message, confirm the church, campus, ministry, group, and audience. Start with the smallest correct group. Never use a whole-church audience when the message is intended for one team or household.

Review before sending
---------------------
Check the subject, links, dates, timezone, attachments, sender identity, and reply address. Send a preview or test message where available. Make sure the message does not expose recipients to one another when a private delivery method is required.

Member preferences
-------------------
Members should be able to update permitted email, SMS, announcement, and ministry preferences. Opting out of a newsletter does not necessarily opt out of essential account or safeguarding messages. Church administrators should document the difference clearly.

Delivery problems
-----------------
Check the member's contact details, consent and preference state, bounce status, provider status, and delivery log. Do not add a member to another list to bypass an opt-out. If a provider reports a failure, include the delivery reference and error category in a private support ticket.

Safe message content
--------------------
Do not include passwords, payment credentials, private pastoral details, or unnecessary personal data. Use a secure authenticated link for sensitive information and make sure links point to the official church domain.
ARTICLE,
            ],
            [
                'slug' => 'roles-permissions-and-safe-administration',
                'title' => 'Roles, permissions, and safe administration',
                'category' => 'Administration & Security',
                'body' => <<<'ARTICLE'
Use least privilege
-------------------
Give each person only the permissions needed for their current responsibility. A volunteer who checks attendance does not normally need finance, user administration, exports, or system settings. Review access whenever a person changes role, campus, or ministry.

Administrator checklist
----------------------
- Use named accounts; never share an administrator login.
- Require strong unique passwords and any available second factor.
- Review active administrators and permissions regularly.
- Remove access when staff or volunteer service ends.
- Keep support and remote-access permissions disabled until needed.
- Record approval for sensitive changes.

Remote support
--------------
Remote support should be time-limited, explicitly approved, scoped to the ticket, and revoked when the investigation ends. Never give a permanent password or unrestricted server access. Verify the support agent identity and review the audit log afterward.

Security incident
-----------------
If an account may be compromised, disable or lock it, rotate affected credentials, preserve relevant audit details, and notify the responsible church administrator. Do not delete logs or investigate by changing evidence in production.
ARTICLE,
            ],
            [
                'slug' => 'how-to-submit-a-useful-support-ticket',
                'title' => 'How to submit a useful support ticket',
                'category' => 'Support & Troubleshooting',
                'body' => <<<'ARTICLE'
Describe one problem clearly
----------------------------
Use a short subject that names the function and symptom, for example: “Attendance export returns an empty CSV for the North Campus.” Avoid subjects such as “It does not work.”

Include the useful facts
-------------------------
- What you were trying to do.
- The exact steps that caused the problem.
- What you expected and what actually happened.
- The date, time, campus, event, or record reference involved.
- The page URL or safe screen name.
- Browser, device, and whether the problem affects other users.
- The visible error message, without passwords or secrets.
- What has already been tried and whether the issue is repeatable.

Do not include
--------------
Passwords, API keys, payment details, full member exports, medical information, private pastoral notes, or confidential screenshots. Replace personal data with placeholders and use the approved secure attachment process for evidence.

What happens next
-----------------
The ticket receives a reference, status, priority, progress, and audit history. You can reply to the ticket and see updates from Central Support. Keep replies on the same ticket so the support team can preserve context. If the issue is urgent or affects safeguarding, follow the church's emergency escalation process as well.
ARTICLE,
            ],
            [
                'slug' => 'data-import-export-and-backup-practices',
                'title' => 'Data import, export, and backup practices',
                'category' => 'Data & Operations',
                'body' => <<<'ARTICLE'
Before an import
-----------------
Confirm the source, owner, field mapping, encoding, duplicate strategy, campus assignment, and approval. Test with a small sample first. Keep the original source file protected and record its date and purpose.

During an import
----------------
Review validation errors before confirming. Do not map a column to a sensitive field just because the names look similar. Stop if the duplicate count, record count, or campus distribution is unexpected. Never run an unreviewed import directly against production.

After an import
---------------
Check a sample of members, households, relationships, attendance, and communication preferences. Confirm that no existing record was overwritten incorrectly. Keep the import summary and approval record according to the church's retention policy.

Exports
-------
Export only the fields and records needed for the approved task. Use a protected destination, limit access, and delete temporary copies after use. Do not upload exports to Community Solutions or attach them to a general support ticket.

Backups
-------
The technical administrator should verify scheduled database and storage backups, retention, encryption, and restoration procedures. A backup is useful only if it can be restored. Test restoration away from production and record the result.
ARTICLE,
            ],
            [
                'slug' => 'church-events-volunteers-and-communication-planning',
                'title' => 'Church events, volunteers, and communication planning',
                'category' => 'Planning & Ministry',
                'body' => <<<'ARTICLE'
Plan the event record first
---------------------------
Create one canonical event with the correct title, campus, timezone, venue, date, sessions, registration limits, attendance method, and visibility. Avoid creating duplicate events for the same service just to change a description.

Volunteer coordination
----------------------
Assign only volunteers who are active and approved for the role. Confirm availability, safeguarding requirements, contact preferences, arrival time, and backup coverage. Do not publish private volunteer notes in a public event description.

Publish clear information
-------------------------
Include the date, timezone, location, audience, accessibility information, registration action, and cancellation or contact instructions. Review the event on a mobile screen before publishing. If the event changes, update the canonical record and notify the affected audience.

After the event
---------------
Reconcile attendance, volunteer service, follow-up tasks, and communication results. Close or archive obsolete registration links. Use the resulting data to improve planning without exposing private member information.
ARTICLE,
            ],
        ];
    }
}
