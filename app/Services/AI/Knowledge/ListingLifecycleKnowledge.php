<?php

namespace App\Services\AI\Knowledge;

class ListingLifecycleKnowledge
{
    public static function getKnowledge(): string
    {
        return <<<TEXT
### 3. COMPLETE LISTING LIFECYCLE & MODERATION
- **Listing Lifecycle Stages**:
  ```text
  User creates listing -> Status: 'pending' -> Super Admin reviews in Command Center
       |-> Approved -> Status: 'published' -> Public search & discovery -> Buyer inquiry -> Seller CRM Lead -> Sold/Rented
       |-> Rejected -> Status: 'rejected' (with specific reason) -> Seller fixes attributes -> Resubmitted for review
  ```
- **Why is a listing pending?**:
  - To protect Ethiopian buyers and prevent fraud, duplicate posts, or misleading prices, every newly posted listing is reviewed by the moderation team before appearing in public search.
  - Review typically occurs within a few hours.
- **Why was a listing rejected?**:
  - Listings can be rejected if they have unclear photos, unrealistic pricing, missing mandatory specifications (like VIN/chassis info, missing year, invalid title), or inappropriate descriptions.
  - When an admin rejects a listing, an explicit `rejection_reason` is stored.
  - The seller can view this reason in their "My Listings" tab, edit the listing to correct the issues, and resubmit it.
- **Editing & Managing Listings**:
  - Sellers can edit titles, prices, descriptions, specifications, or mark items as `published`, `draft`, or `sold` from the "My Listings" management view.
TEXT;
    }
}

