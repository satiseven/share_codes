import { DateTime } from 'luxon'
import { BaseModel, belongsTo, BelongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import User from './User'

export enum EventType {
  'addMailtag' = 1,
  'enableMailtag' = 2,
  'emailCreated' = 3,
  // 'scheduled' = 4, // This feature is removed?
  'pingCreated' = 5,
  // 'invite-friend' = 6, // commented out in the old codebase
  'signatureCreated' = 7,
}

export default class MilestoneEvent extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public userId: string

  @column()
  public eventType: EventType

  @column()
  public isDeleted: boolean = false

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  public get serializedMilestoneEventInfo() {
    return EventType[this.eventType]
  }
}
