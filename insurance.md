# Insurance Policies

## Current Direction

Insurance pricing now belongs to explicit package policies instead of one flat package price table.

The backend shape is:

- `insurance_policies`
- `insurance_policy_items`

An insurance package can have many policies for the current branch:

- Pharmacy policy: covers drug inventory items.
- Lab policy: covers lab test catalog items.
- Services policy: covers billable facility services.

Each policy has its own name, type, active date window, status, and attached item prices.

## Branch Handling

Policies are branch-specific. When a user creates a policy from an insurance package, the branch is set from the current active branch.

That is the right default because package prices are used during visit billing, and visit charges are already tied to the visit branch. Letting users manually choose another branch from the same screen would make accidental cross-branch pricing too easy.

The only case where manual branch selection may be useful later is a super-admin bulk setup screen for multiple branches. That should be a separate workflow.

## Billing Resolution

When a charge is created for an insured visit, pricing should resolve like this:

1. Confirm the visit payer is insurance.
2. Read the visit insurance package.
3. Read the visit branch.
4. Map the charged item to the matching policy type:
   - drug item -> pharmacy policy
   - lab test -> lab policy
   - facility service -> services policy
5. Find an active policy for that package, branch, and policy type.
6. Find an active policy item for the charged item whose effective dates cover today.
7. Use the policy item price; otherwise fall back to the normal catalog/default price.

## Package UI

Insurance package detail should be the home for insurance policies.

The package screen should:

- Show policies as tabs.
- Let users create, edit, and delete policies.
- Let users add attached items while creating a policy.
- Let users add, edit, or remove policy item prices later.
- Show the active branch clearly.
- Keep imports inside the selected policy tab.

## Imports

Insurance imports should target a specific policy, not a generic item type.

The import flow should:

1. User opens an insurance package.
2. User selects or creates a policy.
3. User downloads that policy's template.
4. User uploads the file for preview.
5. User confirms the preview.
6. A queued job imports the policy item prices.

The import remains preview-first because insurance prices directly affect billing and claims.

## Future Suggestions

- Add policy copy tools: copy a policy from one branch/package to another.
- Add a replacement mode later, but keep the current default as "skip overlaps and report conflicts".
- Add policy-level coverage rules beyond price, such as requires authorization, claim code, coverage limit, or copay.
- Add a history view for price changes so billing teams can audit why a charge used a specific price.
- Add a policy comparison view so users can compare two packages side by side.
