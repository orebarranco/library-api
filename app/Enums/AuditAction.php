<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The critical operations the system records. Values are namespaced by the
 * record they touch so an admin can filter a whole area with a prefix.
 */
enum AuditAction: string
{
    case ReservationApproved = 'reservation.approved';
    case ReservationRejected = 'reservation.rejected';
    case LoanCreated = 'loan.created';
    case LoanReturned = 'loan.returned';
    case FineWaived = 'fine.waived';
    case BookUpdated = 'book.updated';
    case RoleAssigned = 'user.role_assigned';
}
