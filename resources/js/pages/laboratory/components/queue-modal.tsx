import { CollectSampleDialog } from './collect-sample-dialog';
import { EnterResultDialog } from './enter-result-dialog';
import { LabResultDialog } from './lab-result-dialog';
import { type ActiveModal } from './queue-utils';
import { ReviewResultDialog } from './review-result-dialog';

export function QueueModal({
    activeModal,
    onOpenChange,
    redirectTo,
}: {
    activeModal: ActiveModal;
    onOpenChange: (open: boolean) => void;
    redirectTo: string;
}) {
    if (activeModal === null) {
        return null;
    }

    return activeModal.mode === 'collect' ? (
        <CollectSampleDialog
            item={activeModal.item}
            open
            onOpenChange={onOpenChange}
            redirectTo={redirectTo}
        />
    ) : activeModal.mode === 'enter' ? (
        <EnterResultDialog
            item={activeModal.item}
            open
            onOpenChange={onOpenChange}
            redirectTo={redirectTo}
        />
    ) : activeModal.mode === 'review' ? (
        <ReviewResultDialog
            item={activeModal.item}
            open
            onOpenChange={onOpenChange}
            redirectTo={redirectTo}
        />
    ) : (
        <LabResultDialog
            item={activeModal.item}
            request={activeModal.request}
            open
            onOpenChange={onOpenChange}
        />
    );
}
