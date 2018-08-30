<?php
namespace Ymca\Salesforcemcloud;

/**
 * Data fetcher.
 *
 * @package Ymca\Salesforcemcloud
 */
class Fetcher {
  /**
   * Fetcher constructor.
   */
  public function __construct()  {
    try {
      // TODO: add implementation.
    }
    catch (\Exception $e) {
      throw new Exception('Failed to run fetcher.');
    }
  }

  public function fetch($connection, $debug = FALSE) {
    $query = "SELECT
        TOP 20
        
        Person.PersonID, Person.FirstName, Person.LastName, Person.Email, Person.HomePhone, Person.WorkPhone, Person.CellPhone,
        Person.Address1, Person.Address2, Person.City, Person.State, Person.ZipCode, Person.BirthDate, Person.Gender,
        Person.Deceased_flag, Person.Opt_Out_Email_flag, Person.HeadOfHousehold,
        
        MembershipDetail.NaturalMembershipID, MembershipDetail.PrimaryMember_personID, MembershipDetail.SoldDate,
        
        MembershipPackage.PackageName,
        
        AlternateKeysHierarchy.AlternateKey1, AlternateKeysHierarchy.AlternateKey2, AlternateKeysHierarchy.AlternateKey3, AlternateKeysHierarchy.AlternateKey4, AlternateKeysHierarchy.AlternateKey5,
        
        DDate.Date, DBranch.SiteFriendlyName
        
        FROM Dim.Person as Person
        
        /* Special logic should be here. */
        LEFT JOIN Fact.MembershipDetail as MembershipDetail
        ON Person.PersonID = MembershipDetail.PrimaryMember_PersonID
        
        /* Special logic should be here. */
        LEFT JOIN Dim.MembershipPackage as MembershipPackage
        ON MembershipDetail.MembershipPackageID = MembershipPackage.MembershipPackageID
        
        LEFT JOIN Dim.AlternateKeysHierarchy as AlternateKeysHierarchy
        ON Person.PersonID = AlternateKeysHierarchy.Customer_PersonID
        
        LEFT JOIN Fact.FacilityUsage as FacilityUsage
        ON MembershipDetail.NaturalMembershipID = FacilityUsage.NaturalMembershipID AND Person.PersonID = FacilityUsage.PersonID 
        
        LEFT JOIN Dim.Date as DDate
        ON FacilityUsage.CheckIn_DateID = DDate.DateID
        
        LEFT JOIN Dim.Branch as DBranch
        ON FacilityUsage.EntryPoint_BranchID = DBranch.BranchID
        
        ORDER BY Person.PersonID DESC";

    /*
     LEFT JOIN Fact.Membership as Membership
     ON MembershipDetail.NaturalMembershipID = Membership.MembershipID OR MembershipDetail.NaturalMembershipID = Membership.NaturalMembershipID

     LEFT JOIN Dim.EntryPoint as EntryPoint
     ON FacilityUsage.EntryPointID = EntryPoint.EntryPointID

     LEFT JOIN Fact.CampRegistration as CampRegistration
     ON Person.PersonID = CampRegistration.Camper_PersonID OR Person.PersonID = CampRegistration.Payee_PersonID OR Person.PersonID = CampRegistration.PayeeSpouse_PersonID

     LEFT JOIN Fact.ChildCareRegistration as ChildCareRegistation
     ON Person.PersonID = ChildCareRegistation.CustomerEnrollee_PersonID OR Person.PersonID = ChildCareRegistation.CustomerPayee_PersonID

     LEFT JOIN Fact.ActivityTransaction as ActivityTransaction
     ON Person.PersonID = ActivityTransaction.CustomerEnrollee_PersonID OR Person.PersonID = ActivityTransaction.CustomerPayee_PersonID

     LEFT JOIN Fact.Gift as Gift
     ON Person.PersonID = Gift.Constituent_PersonID OR Person.PersonID = Gift.SoftCreditConstituent_PersonID OR Person.PersonID = Gift.Solicitor_PersonID

     LEFT JOIN Dim.GiftType as GiftType
     ON Gift.GiftTypeID = GiftType.GiftTypeID

     LEFT JOIN Dim.Campaign as Campaign
     ON Gift.CampaignID = Campaign.CampaignID
     */

    $connection->query($query);
    return $connection->extract();
  }
}
