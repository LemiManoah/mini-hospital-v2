import { CollectSampleDialog } from './collect-sample-dialog';
import { EnterResultDialog } from './enter-result-dialog';
import { LabResultDialog } from './lab-result-dialog';
import { type ActiveModal } from './queue-utils';
import { ReviewResultDialog } from './review-result-dialog';

export function QueueModal({
    activeModal,
    onOpenChange,
    redirectTo,
    labReleasePolicy,
}: {
    activeModal: ActiveModal;
    onOpenChange: (open: boolean) => void;
    redirectTo: string;
    labReleasePolicy: {
        require_review_before_release: boolean;
        require_approval_before_release: boolean;
    };
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
            labReleasePolicy={labReleasePolicy}
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
