<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * CApiMailIcs class is used for work with attachment that contains calendar event or calendar appointment.
 * 
 * @internal
 * 
 * @package Mail
 * @subpackage Classes
 */
class CApiMailIcs
{
	/**
	 * Event identifier.
	 * 
	 * @var string
	 */
	public $Uid;

	/**
	 * Event sequence number.
	 * 
	 * @var int
	 */
	public $Sequence;

	/**
	 * Attendee of the event.
	 * 
	 * @var string
	 */
	public $Attendee;

	/**
	 * Temp file name of the .ics file.
	 * 
	 * @var string
	 */
	public $File;
	
	/**
	 * Type of the event. Possible values:
	 *	'REQUEST' - Object is an appointment. Organizer expects a response to the invitation.
	 *	'REPLY' - Object is an appointment. The recipient replied to the invitation.
	 *	'CANCEL' - Object is an appointment. The event was canceled by the organizer.
	 *	'PUBLISH' - Object is an event for saving to the calendar.
	 *	'SAVE' - Object is an event for saving to the calendar.
	 * 
	 * @var string
	 */
	public $Type;

	/**
	 * Event location.
	 * 
	 * @var string
	 */
	public $Location;

	/**
	 * Event description.
	 * 
	 * @var string
	 */
	public $Description;

	/**
	 * Date of the event.
	 * 
	 * @var string
	 */
	public $When;

	/**
	 * Identifier of calendar in wich the event will be added.
	 * 
	 * @var string
	 */
	public $CalendarId;

	/**
	 * List of calendars.
	 * 
	 * @var array
	 */
	public $Calendars;

	private function __construct()
	{
		$this->Uid = '';
		$this->Sequence = 1;
		$this->Attendee = '';
		$this->File = '';
		$this->Type = '';
		$this->Location = '';
		$this->Description = '';
		$this->When = '';
		$this->CalendarId = '';
		$this->Calendars = array();
	}

	/**
	 * Creates new empty instance.
	 * 
	 * @return CApiMailIcs
	 */
	public static function createInstance()
	{
		return new self();
	}
	
	public function toResponseArray($aParameters = array())
	{
		return array(
			'Uid' => $this->Uid,
			'Sequence' => $this->Sequence,
			'Attendee' => $this->Attendee,
			'File' => $this->File,
			'Type' => $this->Type,
			'Location' => $this->Location,
			'Description' => \MailSo\Base\LinkFinder::NewInstance()
				->Text($this->Description)
				->UseDefaultWrappers(true)
				->CompileText(),
			'When' => $this->When,
			'CalendarId' => $this->CalendarId
		);		
	}
}
