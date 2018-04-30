<?php

namespace tfk\telemarkSkoleBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use DateTime;
use function eZ\Publish\API\Repository\Tests\valueToString;
use tfk\telemarkSkoleBundle\Helper\CalendarHelper;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType;
use jamesiarmes\PhpEws\Enumeration\ItemQueryTraversalType;
use jamesiarmes\PhpEws\Enumeration\UserConfigurationPropertyType;
use jamesiarmes\PhpEws\Request\FindItemType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use jamesiarmes\PhpEws\Request\GetItemType;
use jamesiarmes\PhpEws\Request\GetUserConfigurationType;
use jamesiarmes\PhpEws\Type\CalendarViewType;
use jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use jamesiarmes\PhpEws\Type\ItemIdType;
use jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use Exception;
use jamesiarmes\PhpEws\Type\FindFolderParentType;
use jamesiarmes\PhpEws\Enumeration\FolderQueryTraversalType;
use jamesiarmes\PhpEws\Type\FolderResponseShapeType;
use jamesiarmes\PhpEws\Type\UserConfigurationNameType;
use jamesiarmes\PhpEws\Type\FolderIdType;
use jamesiarmes\PhpEws\Type\FieldOrderType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use jamesiarmes\PhpEws\Enumeration\SortDirectionType;
use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfFieldOrdersType;

class CalendarController extends Controller
{
    public function viewEventAction( $uid )
    {
        $request = new GetItemType();
        $request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();
        $request->ItemShape = new ItemResponseShapeType();
        $request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;

        $item = new ItemIdType();
        $item->Id = str_replace('!SLASH!', '/', $uid);
        $request->ItemIds->ItemId[] = $item;

        $calendarHelper = new CalendarHelper($this->getConfigResolver());
        $client = $calendarHelper->setupClient(true);

        try {
            $response = $client->GetItem($request);
            $response_messages = $response->ResponseMessages->GetItemResponseMessage;
            $event = $response_messages[0]->Items->CalendarItem[0];
            return $this->render('tfktelemarkSkoleBundle:calendar:event.html.twig', array('event' => $event));
        } catch (Exception $e) {
            return $this->render('tfktelemarkSkoleBundle:calendar:event.html.twig');
        } finally {
            unset($calendarHelper);
            unset($client);
        }
    }

    public function viewAllAction( $year = '0', $month = '0' )
    {
        setLocale(LC_TIME, 'no_NO');

        if ($year == 0) {
            $year = date('Y');
        }

        if ($month == 0) {
            $month = date('m');
        }

        $firstDayOfMonth      = new DateTime( $year . '-' . $month . '-1' );
        $numberOfDaysInMonth  = date( 't', mktime( 0, 0, 0, $month, 1, $year ));
        $monthName            = strftime( '%B', mktime( 0, 0, 0, $month, 1, $year ));
        $lastDayOfMonth       = new DateTime( $year . '-' . $month . '-' . $numberOfDaysInMonth );
        $lastDayOfMonth->setTime( 23, 59, 59 );

        $dayOfWeekForFirstDayInMonth = $firstDayOfMonth->format('w');
        $skipBefore = 0;
        if ($dayOfWeekForFirstDayInMonth == 0) //Hvis søndag
            $skipBefore = 6;
        else
            $skipBefore = $dayOfWeekForFirstDayInMonth - 1;

        $dayOfWeekForLastDayInMonth = $lastDayOfMonth->format('w');
        $skipAfter = 0;
        if ($dayOfWeekForLastDayInMonth == 0) //Hvis søndag
            $skipAfter = 0;
        else
            $skipAfter = 7 - $dayOfWeekForLastDayInMonth;

        $calendarFolders = $this->findCalendarFolders();

        $events = $this->findEvents( $firstDayOfMonth, $lastDayOfMonth, $calendarFolders );


        $daysWithEvents = array();

        if ( strlen( $month ) == 1 )
            $month2 = '0' . $month;
        else
            $month2 = $month;

        for ($i = 0; $i < $skipBefore; $i++)
        {
            $daysWithEvents[] = array('day' => '', 'events' => array());
        }

        for ($i = 1; $i <= $numberOfDaysInMonth; $i++)
        {
            if ( strlen( $i ) == 1 )
                $day = '0' . $i;
            else
                $day = $i;

            $daysWithEvents[] = array('day' => $i, 'events' => $this->findEventsForGivenDate( $year .'-'. $month2 .'-'. $day, $events ));
        }


        for ($i = 0; $i < $skipAfter; $i++)
        {
            $daysWithEvents[] = array('day' => '', 'events' => array());
        }


        $today = new DateTime( date("Y-m-d") );
        $endDay = new DateTime( date('Y-m-d', strtotime( "+30 days" ) ) );
        $upcomingEvents = $this->findEvents( $today, $endDay, $calendarFolders );

        return $this->render('tfktelemarkSkoleBundle:calendar:calendar.html.twig', 
            array(
                'year'                          => $year, 
                'month'                         => intval($month), 
                'monthName'                     => $monthName, 
                'events'                        => $events,
                'daysWithEvents'                => $daysWithEvents, 
                'categoriesType'                => $this->findUniqueCategories('3'), 
                'upcomingEvents'                => $upcomingEvents
            )
        );
    }

    private function findEvents( $startDate, $endDate, $folders = false )
    {
        $request = new FindItemType();
        $request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();

        $request->ItemShape = new ItemResponseShapeType();
        $request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;

        // if no folders is set us default for school
        if ( !$folders )
        {
            $folder_id = new DistinguishedFolderIdType();
            $folder_id->Id = DistinguishedFolderIdNameType::CALENDAR;
            $request->ParentFolderIds->DistinguishedFolderId[] = $folder_id;
        }
        else
        {
            foreach ( $folders as $folder )
            {
                $folder_id = new FolderIdType();
                $folder_id->Id = $folder->FolderId->Id;
                $request->ParentFolderIds->FolderId[] = $folder_id;
            }
        }

        $request->CalendarView = new CalendarViewType();
        $request->CalendarView->StartDate = $startDate->format('c');
        $request->CalendarView->EndDate = $endDate->format('c');

        $request->Traversal = ItemQueryTraversalType::SHALLOW;

        // Set sort order.
        /*
        $order = new FieldOrderType();
        $order->FieldURI = new PathToUnindexedFieldType();
        $order->FieldURI->FieldURI = UnindexedFieldURIType::CALENDAR_START;
        //$order->Order = SortDirectionType::DESCENDING;
        $order->Order = SortDirectionType::ASCENDING;
        $request->SortOrder = new NonEmptyArrayOfFieldOrdersType();
        $request->SortOrder->FieldOrder[] = $order;
        */
        
        $itemsToReturn = array();

        $calendarHelper = new CalendarHelper($this->getConfigResolver());
        $client = $calendarHelper->setupClient(true);

        try {
            $response = $client->FindItem($request);
            // Iterate over the results, printing any error messages or event ids.
            $response_messages = $response->ResponseMessages->FindItemResponseMessage;

            foreach ($response_messages as $response_message) {
                $items = $response_message->RootFolder->Items->CalendarItem;
                foreach ($items as $item) {
                    $itemsToReturn[] = $item;
                }
            }
        } catch (Exception $e) {
            echo ('Caught exception: '.$e->getMessage());
        } finally {
            unset($calendarHelper);
            unset($client);
        }

        // sort items
        usort( $itemsToReturn, function($a, $b)
        {
            return strcmp($a->Start, $b->Start);
        });

        return $itemsToReturn;
    }
/*
    private function findEventsForGivenDay( $day, $events )
    {
        $eventsForDay = array();
        foreach ($events as $event) {
            $start = new DateTime($event->Start);

            if (intval($day) == intval($start->format('d'))) {
              $eventsForDay[] = $event;
            }
        }
        return $eventsForDay;
    }
*/
    private function findEventsForGivenDate( $date, $events, $startOnly = false )
    {
        $eventsForDay = array();
        foreach ($events as $event) {
            $start = new DateTime( $event->Start );
            $end   = new DateTime( $event->End );

            if ( $start->format('Y-m-d') != $end->format('Y-m-d') ) {
                if ( $event->IsAllDayEvent )
                    $end->modify( '-1 day' );
                for ( $i = $start; $i <= $end; $i->modify('+1 day') ) {
                    if ( $date ==  $i->format('Y-m-d') ) {
                        $eventsForDay[] = $event;
                    }
                }
            }
            else
                if ( $date == $start->format('Y-m-d') ) {
                    $eventsForDay[] = $event;
                }

        }
        return $eventsForDay;
    }

    private function findUniqueCategories( $wantedCategoryColor )
    {
        $calendarHelper = new CalendarHelper($this->getConfigResolver());
        $client = $calendarHelper->setupClient();

        $request = new GetUserConfigurationType();
        $request->UserConfigurationProperties = UserConfigurationPropertyType::ALL;

        $name = new UserConfigurationNameType();
        $name->DistinguishedFolderId = new DistinguishedFolderIdType();
        $name->DistinguishedFolderId->Id = DistinguishedFolderIdNameType::CALENDAR;
        $name->Name = 'CategoryList';

        $request->UserConfigurationName = $name;

        $response = $client->GetUserConfiguration($request);

        $categories = array();

        $response_messages = $response->ResponseMessages->GetUserConfigurationResponseMessage;

        if ( $response_messages )
            foreach ($response_messages as $response_message) {
                $config = $response_message->UserConfiguration;

                if ( $config )
                {
                    $data = simplexml_load_string(
                        $config->XmlData,
                        'SimpleXMLElement',
                        LIBXML_NOWARNING
                    );

                    foreach ($data->children() as $child) {
                        $color = $child['color'] . "";
                        if ($color == $wantedCategoryColor) {
                            $categories[] = $child['name'] . "";
                        }
                        else
                            $categories[] = $child['name'] . "";
                    }
                }
            }

        return $categories;
    }

    /**
    * get all folders of type calendar needed for each school
    */
    private function findCalendarFolders()
    {
        $configResolver = $this->getConfigResolver();

        $validCalendarFolders = array();
        if ( $configResolver->hasParameter( 'common_calendar_name', 'ews' ) )
            $validCalendarFolders[] = $configResolver->getParameter( 'common_calendar_name', 'ews' );
        if ( $configResolver->hasParameter( 'calendar_name', 'ews' ) )
            $validCalendarFolders[] = $configResolver->getParameter( 'calendar_name', 'ews' );

        $calendarHelper = new CalendarHelper($this->getConfigResolver());
        $client = $calendarHelper->setupClient();

        $request = new GetUserConfigurationType();
        $request->UserConfigurationProperties = UserConfigurationPropertyType::ALL;

        // get calendar folders
        $request = new FindFolderParentType();
        // get all folders recursively
        $request->Traversal = FolderQueryTraversalType::DEEP;
        $request->FolderShape = new FolderResponseShapeType();
        // only retrieve default properties
        $request->FolderShape->BaseShape = DefaultShapeNamesType::DEFAULT_PROPERTIES;
        $request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();
        $request->ParentFolderIds->DistinguishedFolderId = new DistinguishedFolderIdType();
        // specify that we want subfolders of the main calendar folder
        $request->ParentFolderIds->DistinguishedFolderId->Id = DistinguishedFolderIdNameType::CALENDAR;

        try {
            $response = $client->FindFolder($request);
            $response_message = $response->ResponseMessages->FindFolderResponseMessage[ 0 ];

            $result = array();
            foreach ( $response_message->RootFolder->Folders->CalendarFolder as $folder )
                if ( in_array( $folder->DisplayName, $validCalendarFolders ))
                    $result[] = $folder;
        } catch (Exception $e) {
            echo ('Caught exception: '.$e->getMessage());
        } finally {
            unset($calendarHelper);
            unset($client);
        }

        return $result;
    }

}
