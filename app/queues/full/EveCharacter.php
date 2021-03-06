<?php

namespace Seat\EveQueues\Full;

use Carbon\Carbon;
use Seat\EveApi;

class Character {

    public function fire($job, $data) {

        $keyID = $data['keyID'];
        $vCode = $data['vCode'];
        
		$job_record = \SeatQueueInformation::where('jobID', '=', $job->getJobId())->first();

        // Check that we have a valid jobid
        if (!$job_record) {

            // Sometimes the jobs get picked up faster than the submitter could write a
            // database entry about it. So, just wait 5 seconds before we come back and
            // try again
            $job->release(5);
            return;
        }

        // We place the actual API work in our own try catch so that we can report
        // on any critical errors that may have occurred.

        // By default Laravel will requeue a failed job based on --tries, but we
        // dont really want failed api jobs to continually poll the API Server
        try {

            $job_record->status = 'Working';
            $job_record->save();

            $job_record->output = 'Started AccountBalance Update';
            $job_record->save();
            EveApi\Character\AccountBalance::Update($keyID, $vCode);

            $job_record->output = 'Started AssetList Update';
            $job_record->save();        
            EveApi\Character\AssetList::Update($keyID, $vCode);

            $job_record->output = 'Started CharacterSheet Update';
            $job_record->save();        
            EveApi\Character\CharacterSheet::Update($keyID, $vCode);

            $job_record->output = 'Started ContactList Update';
            $job_record->save();        
            EveApi\Character\ContactList::Update($keyID, $vCode);

            $job_record->output = 'Started ContactNotifications Update';
            $job_record->save();        
            EveApi\Character\ContactNotifications::Update($keyID, $vCode);

            $job_record->output = 'Started Contracts Update';
            $job_record->save();        
            EveApi\Character\Contracts::Update($keyID, $vCode);

            $job_record->output = 'Started IndustryJobs Update';
            $job_record->save();        
            EveApi\Character\IndustryJobs::Update($keyID, $vCode);

            $job_record->output = 'Started CharacterInfo Update';
            $job_record->save();
            EveApi\Character\Info::Update($keyID, $vCode);

            $job_record->output = 'Started MailMessages Update';
            $job_record->save();        
            EveApi\Character\MailMessages::Update($keyID, $vCode);

            $job_record->output = 'Started MailingLists Update';
            $job_record->save();        
            EveApi\Character\MailingLists::Update($keyID, $vCode);

            $job_record->output = 'Started Notifications Update';
            $job_record->save();        
            EveApi\Character\Notifications::Update($keyID, $vCode);
            
            $job_record->output = 'Started PlanetaryColonies Update';
            $job_record->save();        
            EveApi\Character\PlanetaryColonies::Update($keyID, $vCode);

            $job_record->output = 'Started MarketOrders Update';
            $job_record->save();        
            EveApi\Character\MarketOrders::Update($keyID, $vCode);

            $job_record->output = 'Started Research Update';
            $job_record->save();        
            EveApi\Character\Research::Update($keyID, $vCode);

            $job_record->output = 'Started SkillInTraining Update';
            $job_record->save();        
            EveApi\Character\SkillInTraining::Update($keyID, $vCode);

            $job_record->output = 'Started SkillQueue Update';
            $job_record->save();        
            EveApi\Character\SkillQueue::Update($keyID, $vCode);

            $job_record->output = 'Started Standings Update';
            $job_record->save();        
            EveApi\Character\Standings::Update($keyID, $vCode);

            $job_record->output = 'Started UpcomingCalendarEvents Update';
            $job_record->save();        
            EveApi\Character\UpcomingCalendarEvents::Update($keyID, $vCode);

            $job_record->output = 'Started WalletJournal Update';
            $job_record->save();        
            EveApi\Character\WalletJournal::Update($keyID, $vCode);

            $job_record->output = 'Started WalletTransactions Update';
            $job_record->save();        
            EveApi\Character\WalletTransactions::Update($keyID, $vCode);

            $job_record->status = 'Done';
            $job_record->output = null;        
            $job_record->save();

            $job->delete();

        } catch (\Exception $e) {

            $job_record->status = 'Error';
            $job_record->output = 'Last status: ' . $job_record->output . PHP_EOL .
                'Error: ' . $e->getCode() . ': ' . $e->getMessage() . PHP_EOL .
                'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL .
                'Trace: ' . $e->getTraceAsString() . PHP_EOL .
                'Previous: ' . $e->getPrevious();
            $job_record->save();

            $job->delete();
        }
    }
}