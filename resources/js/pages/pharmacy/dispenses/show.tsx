import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type DispenseShowPageProps } from '@/types/pharmacy';
import { Head, Link, useForm } from '@inertiajs/react';
import { PlusCircle, Trash2 } from 'lucide-react';

const badgeTone = (value: string | null | undefined): string => {
    switch (value) {
        case 'draft':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'posted':
        case 'dispensed':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'partial':
            return 'border-sky-200 bg-sky-50 text-sky-700';
        case 'cancelled':
            return 'border-rose-200 bg-rose-50 text-rose-700';
        default:
            return 'border-slate-200 bg-slate-50 text-slate-700';
    }
};

export default function DispenseShowPage({
    navigation,
    dispensingRecord,
    availableBatchBalances,
    pharmacyPolicy,
}: DispenseShowPageProps) {
    const postableItems = dispensingRecord.items.filter(
        (item) => item.dispensed_quantity > 0,
    );

    const postForm = useForm<{
        items: Array<{
            dispensing_record_item_id: string;
            allocations: Array<{
                inventory_batch_id: string;
                quantity: string;
            }>;
        }>;
    }>({
        items: postableItems.map((item) => ({
            dispensing_record_item_id: item.id,
            allocations: item.allocations.map((allocation) => ({
                inventory_batch_id: allocation.inventory_batch_id,
                quantity: allocation.quantity.toFixed(3),
            })),
        })),
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: navigation.section_title, href: navigation.section_href },
        {
            title: navigation.queue_title ?? 'Pharmacy Queue',
            href: navigation.queue_href ?? '/pharmacy/queue',
        },
        {
            title: dispensingRecord.prescription?.id
                ? 'Prescription'
                : 'Dispense Record',
            href: dispensingRecord.prescription?.id
                ? `/pharmacy/prescriptions/${dispensingRecord.prescription.id}`
                : `/pharmacy/dispenses/${dispensingRecord.id}`,
        },
        {
            title: dispensingRecord.dispense_number,
            href: `/pharmacy/dispenses/${dispensingRecord.id}`,
        },
    ];

    const addAllocation = (lineIndex: number) => {
        const updated = [...postForm.data.items];
        updated[lineIndex] = {
            ...updated[lineIndex],
            allocations: [
                ...updated[lineIndex].allocations,
                { inventory_batch_id: '', quantity: '' },
            ],
        };
        postForm.setData('items', updated);
    };

    const removeAllocation = (lineIndex: number, allocationIndex: number) => {
        const updated = [...postForm.data.items];
        updated[lineIndex] = {
            ...updated[lineIndex],
            allocations: updated[lineIndex].allocations.filter(
                (_, index) => index !== allocationIndex,
            ),
        };
        postForm.setData('items', updated);
    };

    const updateAllocation = (
        lineIndex: number,
        allocationIndex: number,
        field: 'inventory_batch_id' | 'quantity',
        value: string,
    ) => {
        const updated = [...postForm.data.items];
        const allocations = [...updated[lineIndex].allocations];
        allocations[allocationIndex] = {
            ...allocations[allocationIndex],
            [field]: value,
        };
        updated[lineIndex] = {
            ...updated[lineIndex],
            allocations,
        };
        postForm.setData('items', updated);
    };

    const batchOptionsFor = (inventoryItemId: string) =>
        availableBatchBalances
            .filter((batch) => batch.inventory_item_id === inventoryItemId)
            .map((batch) => ({
                value: batch.inventory_batch_id,
                label: `${batch.batch_number ?? 'No batch'} | Qty ${batch.quantity.toFixed(3)}${batch.expiry_date ? ` | Exp ${batch.expiry_date}` : ''}`,
            }));

    const errorFor = (index: number, itemId: string, suffix: string) =>
        postForm.errors[
            `items.${index}.${suffix}` as keyof typeof postForm.errors
        ] ??
        postForm.errors[
            `items.${itemId}.${suffix}` as keyof typeof postForm.errors
        ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={dispensingRecord.dispense_number} />

            <div className="m-4 flex max-w-6xl flex-col gap-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold">
                                {dispensingRecord.dispense_number}
                            </h1>
                            <Badge
                                variant="outline"
                                className={badgeTone(dispensingRecord.status)}
                            >
                                {dispensingRecord.status_label ?? 'Unknown'}
                            </Badge>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                            <span>
                                Patient:{' '}
                                {dispensingRecord.patient?.full_name ?? '-'}
                            </span>
                            <span>
                                Visit: {dispensingRecord.visit_number ?? '-'}
                            </span>
                            <span>
                                Dispense time:{' '}
                                {dispensingRecord.dispensed_at
                                    ? new Date(
                                          dispensingRecord.dispensed_at,
                                      ).toLocaleString()
                                    : '-'}
                            </span>
                            <span>
                                Prepared by:{' '}
                                {dispensingRecord.dispensed_by ?? '-'}
                            </span>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        {dispensingRecord.prescription?.id ? (
                            <Button variant="outline" asChild>
                                <Link
                                    href={`/pharmacy/prescriptions/${dispensingRecord.prescription.id}`}
                                >
                                    Back To Prescription
                                </Link>
                            </Button>
                        ) : null}
                        <Button variant="outline" asChild>
                            <Link href={navigation.queue_href ?? '/pharmacy/queue'}>
                                Back To Queue
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Posting Policy</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-muted-foreground">
                        <p>
                            Batch tracking:{' '}
                            {pharmacyPolicy.batch_tracking_enabled
                                ? 'pharmacists must allocate the dispensed quantity to source batches before posting.'
                                : 'the system will auto-allocate batches using FEFO from available pharmacy stock.'}
                        </p>
                        <p>
                            Partial dispensing:{' '}
                            {pharmacyPolicy.allow_partial_dispense
                                ? 'allowed'
                                : 'disabled at tenant level'}
                            .
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Header Details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm md:grid-cols-2">
                        <div className="space-y-1">
                            <div>
                                Location:{' '}
                                {dispensingRecord.inventory_location?.name ??
                                    '-'}
                            </div>
                            <div className="text-muted-foreground">
                                Code:{' '}
                                {dispensingRecord.inventory_location
                                    ?.location_code ?? '-'}
                            </div>
                        </div>
                        <div className="space-y-1">
                            <div>
                                Prescription status:{' '}
                                {dispensingRecord.prescription?.status_label ??
                                    '-'}
                            </div>
                            <div className="text-muted-foreground">
                                Diagnosis:{' '}
                                {dispensingRecord.prescription
                                    ?.primary_diagnosis ?? '-'}
                            </div>
                        </div>
                        {dispensingRecord.notes ? (
                            <div className="text-muted-foreground md:col-span-2">
                                {dispensingRecord.notes}
                            </div>
                        ) : null}
                    </CardContent>
                </Card>

                {dispensingRecord.can_post ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Post Stock Movements</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                {pharmacyPolicy.batch_tracking_enabled
                                    ? 'Allocate each dispensed quantity to a pharmacy batch, then post the dispense to write stock movements.'
                                    : 'Review the drafted quantities and post. The system will auto-allocate available pharmacy batches for you.'}
                            </p>

                            <InputError message={postForm.errors.items} />
                            <InputError
                                message={postForm.errors.dispensing_record}
                            />

                            {postableItems.length === 0 ? (
                                <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                    This record has no local dispense quantity,
                                    so posting will only confirm the recorded
                                    external outcome.
                                </div>
                            ) : null}

                            <form
                                className="space-y-4"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    postForm.post(
                                        `/pharmacy/dispenses/${dispensingRecord.id}/post`,
                                    );
                                }}
                            >
                                {pharmacyPolicy.batch_tracking_enabled
                                    ? postableItems.map((item, lineIndex) => {
                                          const formItem =
                                              postForm.data.items[lineIndex];
                                          const allocations =
                                              formItem?.allocations ?? [];
                                          const allocatedQuantity =
                                              allocations.reduce(
                                                  (sum, allocation) =>
                                                      sum +
                                                      Number(
                                                          allocation.quantity ||
                                                              0,
                                                      ),
                                                  0,
                                              );
                                          const remainingAllocation =
                                              Math.max(
                                                  item.dispensed_quantity -
                                                      allocatedQuantity,
                                                  0,
                                              );
                                          const batchOptions = batchOptionsFor(
                                              item.inventory_item_id,
                                          );

                                          return (
                                              <div
                                                  key={item.id}
                                                  className="rounded-lg border p-4"
                                              >
                                                  <div className="mb-3">
                                                      <div className="font-medium">
                                                          {item.item_name ??
                                                              item.generic_name ??
                                                              'Medication'}
                                                      </div>
                                                      <div className="text-sm text-muted-foreground">
                                                          Dispensed:{' '}
                                                          {item.dispensed_quantity.toFixed(
                                                              3,
                                                          )}{' '}
                                                          | Balance:{' '}
                                                          {item.balance_quantity.toFixed(
                                                              3,
                                                          )}
                                                      </div>
                                                  </div>

                                                  {allocations.length > 0 ? (
                                                      <div className="space-y-3">
                                                          {allocations.map(
                                                              (
                                                                  allocation,
                                                                  allocationIndex,
                                                              ) => (
                                                                  <div
                                                                      key={`${item.id}-${allocationIndex}`}
                                                                      className="grid gap-3 rounded border border-dashed p-3 md:grid-cols-[1.6fr_1fr_auto]"
                                                                  >
                                                                      <div className="grid gap-2">
                                                                          <Label>
                                                                              Source Batch
                                                                          </Label>
                                                                          <SearchableSelect
                                                                              options={batchOptions}
                                                                              value={
                                                                                  allocation.inventory_batch_id
                                                                              }
                                                                              onValueChange={(
                                                                                  value,
                                                                              ) =>
                                                                                  updateAllocation(
                                                                                      lineIndex,
                                                                                      allocationIndex,
                                                                                      'inventory_batch_id',
                                                                                      value,
                                                                                  )
                                                                              }
                                                                              placeholder="Select batch"
                                                                              emptyMessage="No matching batches."
                                                                          />
                                                                          <InputError
                                                                              message={
                                                                                  errorFor(
                                                                                      lineIndex,
                                                                                      item.id,
                                                                                      `allocations.${allocationIndex}.inventory_batch_id`,
                                                                                  ) as string
                                                                              }
                                                                          />
                                                                      </div>
                                                                      <div className="grid gap-2">
                                                                          <Label>
                                                                              Quantity
                                                                          </Label>
                                                                          <Input
                                                                              type="number"
                                                                              step="0.001"
                                                                              min="0"
                                                                              value={
                                                                                  allocation.quantity
                                                                              }
                                                                              onChange={(
                                                                                  event,
                                                                              ) =>
                                                                                  updateAllocation(
                                                                                      lineIndex,
                                                                                      allocationIndex,
                                                                                      'quantity',
                                                                                      event.target.value,
                                                                                  )
                                                                              }
                                                                          />
                                                                          <InputError
                                                                              message={
                                                                                  errorFor(
                                                                                      lineIndex,
                                                                                      item.id,
                                                                                      `allocations.${allocationIndex}.quantity`,
                                                                                  ) as string
                                                                              }
                                                                          />
                                                                      </div>
                                                                      <div className="flex items-end">
                                                                          <Button
                                                                              type="button"
                                                                              size="icon"
                                                                              variant="ghost"
                                                                              onClick={() =>
                                                                                  removeAllocation(
                                                                                      lineIndex,
                                                                                      allocationIndex,
                                                                                  )
                                                                              }
                                                                          >
                                                                              <Trash2 className="h-4 w-4" />
                                                                          </Button>
                                                                      </div>
                                                                  </div>
                                                              ),
                                                          )}

                                                          <div className="flex flex-col gap-3 rounded border border-dashed p-3 md:flex-row md:items-center md:justify-between">
                                                              <div className="text-sm text-muted-foreground">
                                                                  Allocated:{' '}
                                                                  {allocatedQuantity.toFixed(
                                                                      3,
                                                                  )}{' '}
                                                                  of{' '}
                                                                  {item.dispensed_quantity.toFixed(
                                                                      3,
                                                                  )}
                                                                  {remainingAllocation >
                                                                  0 ? (
                                                                      <span>
                                                                          {' '}
                                                                          |
                                                                          Remaining:{' '}
                                                                          {remainingAllocation.toFixed(
                                                                              3,
                                                                          )}
                                                                      </span>
                                                                  ) : (
                                                                      <span>
                                                                          {' '}
                                                                          |
                                                                          Fully
                                                                          allocated
                                                                      </span>
                                                                  )}
                                                              </div>
                                                              <Button
                                                                  type="button"
                                                                  size="sm"
                                                                  variant="outline"
                                                                  onClick={() =>
                                                                      addAllocation(
                                                                          lineIndex,
                                                                      )
                                                                  }
                                                                  disabled={
                                                                      batchOptions.length ===
                                                                          0 ||
                                                                      remainingAllocation <=
                                                                          0
                                                                  }
                                                              >
                                                                  <PlusCircle className="mr-2 h-4 w-4" />
                                                                  Add Batch
                                                              </Button>
                                                          </div>
                                                      </div>
                                                  ) : (
                                                      <div className="flex items-center justify-between rounded border border-dashed p-3">
                                                          <p className="text-sm text-muted-foreground">
                                                              No pharmacy batches
                                                              selected yet.
                                                          </p>
                                                          <Button
                                                              type="button"
                                                              size="sm"
                                                              variant="outline"
                                                              onClick={() =>
                                                                  addAllocation(
                                                                      lineIndex,
                                                                  )
                                                              }
                                                              disabled={
                                                                  batchOptions.length ===
                                                                  0
                                                              }
                                                          >
                                                              <PlusCircle className="mr-2 h-4 w-4" />
                                                              Add Batch
                                                          </Button>
                                                      </div>
                                                  )}

                                                  <InputError
                                                      message={
                                                          errorFor(
                                                              lineIndex,
                                                              item.id,
                                                              'allocations',
                                                          ) as string
                                                      }
                                                  />
                                              </div>
                                          );
                                      })
                                    : postableItems.map((item) => (
                                          <div
                                              key={item.id}
                                              className="rounded-lg border p-4"
                                          >
                                              <div className="font-medium">
                                                  {item.item_name ??
                                                      item.generic_name ??
                                                      'Medication'}
                                              </div>
                                              <div className="text-sm text-muted-foreground">
                                                  {item.dispensed_quantity.toFixed(
                                                      3,
                                                  )}{' '}
                                                  will be auto-allocated from
                                                  the earliest-expiring
                                                  available batches in{' '}
                                                  {dispensingRecord.inventory_location
                                                      ?.name ?? 'the pharmacy'}
                                                  .
                                              </div>
                                          </div>
                                      ))}

                                <div className="flex justify-end">
                                    <Button
                                        type="submit"
                                        disabled={postForm.processing}
                                    >
                                        Post Dispense
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                ) : null}

                <Card>
                    <CardHeader>
                        <CardTitle>Line Snapshot</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {dispensingRecord.items.map((item) => (
                            <div
                                key={item.id}
                                className="space-y-3 rounded-lg border p-4"
                            >
                                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div className="space-y-1">
                                        <div className="font-medium">
                                            {item.item_name ??
                                                item.generic_name ??
                                                'Medication'}
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            Prescribed:{' '}
                                            {item.prescribed_quantity.toFixed(
                                                3,
                                            )}{' '}
                                            • Dispensed:{' '}
                                            {item.dispensed_quantity.toFixed(3)}{' '}
                                            • Balance:{' '}
                                            {item.balance_quantity.toFixed(3)}
                                        </div>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        className={badgeTone(
                                            item.dispense_status,
                                        )}
                                    >
                                        {item.dispense_status_label ??
                                            'Unknown'}
                                    </Badge>
                                </div>

                                {item.external_pharmacy ? (
                                    <div className="rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                                        External pharmacy:{' '}
                                        {item.external_reason ?? 'Marked'}
                                    </div>
                                ) : null}

                                {item.substitution_item_name ? (
                                    <div className="text-sm text-muted-foreground">
                                        Substitution:{' '}
                                        {item.substitution_item_name}
                                    </div>
                                ) : null}

                                {item.allocations.length > 0 ? (
                                    <div className="space-y-2 rounded-md border border-dashed p-3 text-sm text-muted-foreground">
                                        <div className="font-medium text-foreground">
                                            Batch Allocations
                                        </div>
                                        {item.allocations.map((allocation) => (
                                            <div
                                                key={allocation.id}
                                                className="flex flex-wrap gap-x-3 gap-y-1"
                                            >
                                                <span>
                                                    Batch:{' '}
                                                    {allocation.batch_number_snapshot ??
                                                        'Unspecified'}
                                                </span>
                                                <span>
                                                    Qty:{' '}
                                                    {allocation.quantity.toFixed(
                                                        3,
                                                    )}
                                                </span>
                                                <span>
                                                    Exp:{' '}
                                                    {allocation.expiry_date_snapshot ??
                                                        '-'}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                ) : null}

                                {item.notes ? (
                                    <div className="text-sm text-muted-foreground">
                                        {item.notes}
                                    </div>
                                ) : null}
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
