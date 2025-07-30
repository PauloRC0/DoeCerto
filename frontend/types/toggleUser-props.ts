export type UserType = "donor" | "ong";

export type ToggleUserProps = {
  selected: UserType;
  setSelected: React.Dispatch<React.SetStateAction<UserType>>;
};
