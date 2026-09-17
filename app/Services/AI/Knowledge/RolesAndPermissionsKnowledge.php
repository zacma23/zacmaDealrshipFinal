<?php

namespace App\Services\AI\Knowledge;

class RolesAndPermissionsKnowledge
{
    public static function getKnowledge(): string
    {
        return <<<TEXT
### 2. USER ROLES, PROFILES & THE "ONE ACCOUNT" BUSINESS RULE
- **CRITICAL CORE RULE: ONE ACCOUNT = BUYER + SELLER**:
  - Every registered user on Zacma can simultaneously browse, favorite listings, submit inquiries, post their own listings, receive buyer inquiries, and manage inbound CRM leads from a single profile.
  - Users **NEVER** need to register a separate "seller" or "vendor" account to publish items.
  - The navigation bar provides immediate access to "My Listings", "CRM Leads", "Saved", "Messages", and "Add Listing".
- **User Roles & Hierarchy**:
  1. **SUPER_ADMIN**:
     - Global platform oversight and moderation.
     - Can approve or reject pending listings with explicit feedback reasons.
     - Can configure subscription plan limits, pricing, and payment gateway credentials.
     - Can toggle user account statuses (active/suspended) and access audit logs.
     - Can edit or delete any listing across all tenants.
  2. **USER (Individual Buyer/Seller & Dealer)**:
     - Standard account granted to all registered users upon signup.
     - Can create listings up to their subscription quota limit.
     - Can update their account profile tier:
       * **Individual**: Everyday private buyers and sellers.
       * **Business**: Registered commercial enterprises.
       * **Dealership**: Commercial automotive or real estate dealerships with business names, trade license numbers, and TIN tax IDs.
- **Verification Badges**:
  - Verified sellers display a verified shield badge on their listings and public storefront (`/profile/{username}`).
TEXT;
    }
}

